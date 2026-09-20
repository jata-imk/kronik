<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\MetodoAmortizacion;
use App\Enums\PeriodicidadCredito;
use App\Enums\SolicitudEstado;
use App\Enums\UserStatus;
use App\Models\Cliente;
use App\Models\ProductoVersion;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\Credito\SimuladorCreditoSimple;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SolicitudService
{
    private const CAMPOS = ['producto_version_id', 'monto', 'plazo', 'periodicidad', 'metodo', 'destino', 'fecha_estimada'];

    public function __construct(
        private readonly FechaEmpresa $fecha,
        private readonly ProductoVersionService $productos,
        private readonly SimuladorCreditoSimple $simulador,
        private readonly ActivityLogService $actividad,
    ) {}

    public function crear(array $data, User $actor): Solicitud
    {
        Gate::forUser($actor)->authorize('create', Solicitud::class);

        return DB::transaction(function () use ($data, $actor) {
            User::query()->lockForUpdate()->findOrFail($actor->id);
            $cliente = Cliente::query()->lockForUpdate()->findOrFail($data['cliente_id']);
            $this->sucursal($cliente->sucursal_id, $actor);
            $hash = hash('sha256', json_encode(Arr::only($data, ['cliente_id', ...self::CAMPOS]), JSON_THROW_ON_ERROR));
            $existing = Solicitud::where('creada_por', $actor->id)->where('clave_creacion', $data['clave_creacion'])->first();
            if ($existing) {
                if (! hash_equals($existing->creacion_hash, $hash)) {
                    $this->error('clave_creacion', 'Esta operación ya se registró con otros datos. Abre una nueva solicitud.');
                }

                return $existing;
            }
            $this->productoDisponible($data['producto_version_id'] ?? null);
            $solicitud = Solicitud::create([
                ...Arr::only($data, self::CAMPOS),
                'cliente_id' => $cliente->id,
                'sucursal_id' => $cliente->sucursal_id,
                'creada_por' => $actor->id,
                'responsable_id' => $actor->id,
                'clave_creacion' => $data['clave_creacion'],
                'creacion_hash' => $hash,
                'estado' => SolicitudEstado::Borrador,
            ]);
            $this->evento($solicitud, $actor, 'creada', ActivityEvent::ApplicationCreated);

            return $solicitud;
        });
    }

    public function guardar(Solicitud $solicitud, array $data, User $actor): Solicitud
    {
        return DB::transaction(function () use ($solicitud, $data, $actor) {
            $solicitud = $this->bloquear($solicitud, $actor, 'update', $data['lock_version']);
            if (! $solicitud->estado->editable()) {
                $this->error('solicitud', 'La solicitud enviada no admite cambios de condiciones.');
            }
            $this->productoDisponible(array_key_exists('producto_version_id', $data) ? $data['producto_version_id'] : $solicitud->producto_version_id);
            $solicitud->fill(Arr::only($data, self::CAMPOS));
            $solicitud->lock_version++;
            $solicitud->save();
            $this->evento($solicitud, $actor, 'borrador_actualizado', ActivityEvent::ApplicationUpdated);

            return $solicitud;
        });
    }

    public function enviar(Solicitud $solicitud, int $version, User $actor): Solicitud
    {
        return DB::transaction(function () use ($solicitud, $version, $actor) {
            $solicitud = $this->bloquear($solicitud, $actor, 'update', $version);
            if (! $solicitud->estado->editable()) {
                $this->error('solicitud', 'Esta solicitud ya fue enviada a revisión.');
            }
            foreach (self::CAMPOS as $field) {
                if (blank($solicitud->$field)) {
                    $this->error($field, 'Completa este dato antes de enviar la solicitud a revisión.');
                }
            }
            // Same client -> product lock order as creation and deletion flows.
            $cliente = Cliente::query()->lockForUpdate()->findOrFail($solicitud->cliente_id);
            $producto = $this->productoDisponible($solicitud->producto_version_id);
            // A contractual date is not a UTC instant. Rebuild it in the
            // lender's zone instead of converting Eloquent's midnight cast.
            $fecha = CarbonImmutable::parse($solicitud->fecha_estimada->toDateString(), $this->fecha->zonaHoraria())->startOfDay();
            if ($fecha->lt($this->fecha->hoy())) {
                $this->error('fecha_estimada', 'La fecha estimada no puede ser anterior a hoy.');
            }
            $simulacion = $this->simulador->simular($producto, $solicitud->monto,
                PeriodicidadCredito::from($solicitud->periodicidad), $solicitud->plazo,
                MetodoAmortizacion::from($solicitud->metodo), $fecha);
            $politica = app(OriginacionPoliticaService::class)->vigente($producto->id);
            $snapshot = [
                'formato' => 1,
                'politica_originacion' => $politica?->only(['id', 'numero', 'condiciones', 'snapshot_hash']),
                'identidad_cliente' => app(SolicitudExpedienteService::class)->identidad($cliente),
                'condiciones' => $solicitud->only(self::CAMPOS),
                'cliente_id' => $solicitud->cliente_id,
                'cliente' => $cliente
                    ->only(['primer_nombre', 'segundo_nombre', 'apellido_paterno', 'apellido_materno',
                        'fecha_nacimiento', 'ocupacion', 'actividad_economica', 'ingresos_mensuales', 'egresos_mensuales', 'origen_recursos']),
                'sucursal_id' => $solicitud->sucursal_id,
                'producto' => $producto->snapshot,
                'simulacion_informativa' => $simulacion,
            ];
            $revision = $solicitud->revisiones()->create([
                'numero' => ($solicitud->revisiones()->max('numero') ?? 0) + 1, 'producto_version_id' => $producto->id, 'creada_por' => $actor->id,
                'snapshot' => $snapshot, 'snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)),
            ]);
            $this->productos->registrarUso($producto, 'solicitud_revisiones', $revision->id);
            $solicitud->update(['estado' => SolicitudEstado::EnRevision, 'enviada_en' => now(), 'lock_version' => $solicitud->lock_version + 1]);
            $this->evento($solicitud, $actor, 'enviada', ActivityEvent::ApplicationSubmitted, ['revision' => $revision->numero]);

            return $solicitud;
        });
    }

    public function asignar(Solicitud $solicitud, int $version, int $responsableId, User $actor): Solicitud
    {
        return DB::transaction(function () use ($solicitud, $version, $responsableId, $actor) {
            $solicitud = $this->bloquear($solicitud, $actor, 'assign', $version);
            if (in_array($solicitud->estado, [SolicitudEstado::Rechazada, SolicitudEstado::Cancelada], true)) {
                $this->error('solicitud', 'La solicitud está cerrada y no admite reasignaciones.');
            }
            $responsable = User::findOrFail($responsableId);
            if ($responsable->status !== UserStatus::Active
                || ! $responsable->sucursales()->whereKey($solicitud->sucursal_id)->exists()
                || ! Gate::forUser($responsable)->allows('viewAny', Solicitud::class)) {
                $this->error('responsable_id', 'Selecciona una persona activa con acceso a solicitudes y a esta sucursal en el equipo actual.');
            }
            $solicitud->update(['responsable_id' => $responsable->id, 'lock_version' => $solicitud->lock_version + 1]);
            $this->evento($solicitud, $actor, 'asignada', ActivityEvent::ApplicationAssigned, ['responsable_id' => $responsable->id]);

            return $solicitud;
        });
    }

    public function resolver(Solicitud $solicitud, array $data, User $actor): Solicitud
    {
        Gate::forUser($actor)->authorize(($data['accion'] ?? null) === 'cancelar' ? 'cancel' : 'review', $solicitud);
        if (is_string($data['motivo'] ?? null)) {
            $data['motivo'] = trim($data['motivo']);
        }
        $data = Validator::make($data, [
            'lock_version' => 'required|integer|min:0',
            'accion' => ['required', Rule::in(['devolver', 'rechazar', 'cancelar'])],
            'motivo' => ['required', 'string', 'min:10', 'max:2000', 'regex:/\S/u'],
            'responsable_id' => 'required_if:accion,devolver|nullable|integer|exists:users,id',
        ])->validate();

        return DB::transaction(function () use ($solicitud, $data, $actor) {
            $solicitud = $this->bloquear($solicitud, $actor, $data['accion'] === 'cancelar' ? 'cancel' : 'review', $data['lock_version']);
            $permitido = $data['accion'] === 'cancelar'
                ? in_array($solicitud->estado, [SolicitudEstado::Borrador, SolicitudEstado::Devuelta, SolicitudEstado::EnRevision, SolicitudEstado::Aprobada], true)
                : ($solicitud->estado === SolicitudEstado::EnRevision || ($data['accion'] === 'devolver' && $solicitud->estado === SolicitudEstado::Aprobada));
            if (! $permitido) {
                $this->error('solicitud', 'Esta acción no está disponible en el estado actual de la solicitud. Actualiza la página.');
            }
            $responsableId = $solicitud->responsable_id;
            if ($data['accion'] === 'devolver') {
                $responsable = User::findOrFail($data['responsable_id']);
                if ($responsable->status !== UserStatus::Active
                    || ! $responsable->sucursales()->whereKey($solicitud->sucursal_id)->exists()
                    || ! Gate::forUser($responsable)->allows('viewAny', Solicitud::class)
                    || ! $responsable->can('update solicitudes')) {
                    $this->error('responsable_id', 'Selecciona una persona activa de esta sucursal con acceso para corregir solicitudes.');
                }
                $responsableId = $responsable->id;
            }
            [$estado, $event, $tipo] = match ($data['accion']) {
                'devolver' => [SolicitudEstado::Devuelta, ActivityEvent::ApplicationReturned, 'devuelta'],
                'rechazar' => [SolicitudEstado::Rechazada, ActivityEvent::ApplicationRejected, 'rechazada'],
                'cancelar' => [SolicitudEstado::Cancelada, ActivityEvent::ApplicationCancelled, 'cancelada'],
            };
            $revision = $solicitud->revisiones()->latest('numero')->first();
            $resolucion = $solicitud->resoluciones()->create([
                'solicitud_revision_id' => $revision?->id, 'actor_id' => $actor->id,
                'responsable_id' => $responsableId, 'accion' => $data['accion'], 'motivo' => trim($data['motivo']),
            ]);
            $solicitud->update(['estado' => $estado, 'responsable_id' => $responsableId, 'lock_version' => $solicitud->lock_version + 1]);
            // Only identifiers in the shared timeline; never copy reasons into technical logs.
            $this->evento($solicitud, $actor, $tipo, $event, ['resolucion_id' => $resolucion->id, 'revision' => $revision?->numero]);

            return $solicitud;
        });
    }

    public function dictaminar(Solicitud $solicitud, array $data, User $actor): Solicitud
    {
        $pld = ($data['tipo_dictamen'] ?? null) === 'pld';
        $ability = $pld ? 'compliance' : 'evaluate';
        Gate::forUser($actor)->authorize($ability, $solicitud);
        foreach (['fundamento', 'fuentes', 'metodologia'] as $field) {
            if (is_string($data[$field] ?? null)) {
                $data[$field] = trim($data[$field]);
            }
        }
        $data = Validator::make($data, [
            'lock_version' => 'required|integer|min:0',
            'tipo_dictamen' => ['required', Rule::in(['evaluacion', 'pld'])],
            'resultado' => ['required', Rule::in($pld ? ['sin_observaciones', 'pendiente', 'bloqueada'] : ['favorable', 'pendiente', 'desfavorable'])],
            'fundamento' => 'required|string|min:20|max:4000',
            'fuentes' => 'required|string|min:10|max:2000',
            'metodologia' => 'required|string|min:5|max:500',
            'nivel_riesgo' => $pld ? ['required', Rule::in(['bajo', 'medio', 'alto', 'sin_determinar'])] : ['prohibited'],
        ], ['nivel_riesgo.prohibited' => 'El nivel de riesgo se registra únicamente en la revisión PLD.'])->validate();
        if ($pld && $data['resultado'] === 'sin_observaciones' && $data['nivel_riesgo'] === 'sin_determinar') {
            $this->error('nivel_riesgo', 'Determina el nivel de riesgo antes de concluir sin observaciones.');
        }

        return DB::transaction(function () use ($solicitud, $data, $actor, $ability) {
            $solicitud = $this->bloquear($solicitud, $actor, $ability, $data['lock_version']);
            if ($solicitud->estado !== SolicitudEstado::EnRevision) {
                $this->error('solicitud', 'Solo puedes registrar dictámenes cuando la solicitud está en revisión.');
            }
            $revision = $solicitud->revisiones()->latest('numero')->firstOrFail();
            $cliente = Cliente::query()->lockForUpdate()->findOrFail($solicitud->cliente_id);
            $dictamen = $solicitud->dictamenes()->create([
                'solicitud_revision_id' => $revision->id, 'actor_id' => $actor->id,
                'tipo' => $data['tipo_dictamen'], 'resultado' => $data['resultado'],
                'contenido' => [...Arr::only($data, ['fundamento', 'fuentes', 'metodologia', 'nivel_riesgo']),
                    'expediente_hash' => app(SolicitudExpedienteService::class)->huella($cliente)],
            ]);
            $solicitud->update(['lock_version' => $solicitud->lock_version + 1]);
            $this->evento($solicitud, $actor, 'dictamen_registrado', ActivityEvent::ApplicationAssessmentRecorded,
                ['dictamen_id' => $dictamen->id, 'revision' => $revision->numero]);

            return $solicitud;
        });
    }

    public function aprobar(Solicitud $solicitud, int $version, string $motivo, User $actor): Solicitud
    {
        Gate::forUser($actor)->authorize('approve', $solicitud);
        $motivo = trim($motivo);
        Validator::make(['motivo' => $motivo], ['motivo' => 'required|string|min:10|max:2000'])->validate();

        return DB::transaction(function () use ($solicitud, $version, $motivo, $actor) {
            $solicitud = $this->bloquear($solicitud, $actor, 'approve', $version);
            Cliente::query()->lockForUpdate()->findOrFail($solicitud->cliente_id);
            if ($solicitud->producto_version_id) {
                ProductoVersion::query()->lockForUpdate()->findOrFail($solicitud->producto_version_id);
            }
            $resultado = app(SolicitudRequisitosService::class)->evaluar($solicitud, $actor);
            if (! $resultado['puede_aprobar']) {
                $this->error('aprobacion', collect($resultado['requisitos'])->where('cumplido', false)->pluck('mensaje')->implode(' '));
            }
            $resolucion = $solicitud->resoluciones()->create([
                'solicitud_revision_id' => $resultado['evidencia']['revision_id'], 'actor_id' => $actor->id,
                'responsable_id' => $solicitud->responsable_id, 'accion' => 'aprobar', 'motivo' => $motivo,
                'evidencia' => $resultado['evidencia'],
                'vigente_hasta' => $this->fecha->hoy()->addDays($resultado['politica']['condiciones']['vigencia_dias'] - 1)->toDateString(),
            ]);
            $solicitud->update(['estado' => SolicitudEstado::Aprobada, 'lock_version' => $solicitud->lock_version + 1]);
            $this->evento($solicitud, $actor, 'aprobada', ActivityEvent::ApplicationApproved, ['resolucion_id' => $resolucion->id]);

            return $solicitud;
        });
    }

    private function bloquear(Solicitud $solicitud, User $actor, string $ability, int $version): Solicitud
    {
        $solicitud = Solicitud::query()->lockForUpdate()->findOrFail($solicitud->id);
        Gate::forUser($actor)->authorize($ability, $solicitud);
        $this->sucursal($solicitud->sucursal_id, $actor);
        if ($solicitud->lock_version !== $version) {
            $this->error('solicitud', 'La solicitud cambió. Actualiza la página antes de continuar.');
        }

        return $solicitud;
    }

    private function sucursal(?int $id, User $actor): void
    {
        if (! $id || (int) $actor->current_sucursal_id !== $id || ! $actor->currentSucursal?->activa) {
            $this->error('sucursal', 'Selecciona la sucursal activa responsable del cliente o de la solicitud.');
        }
    }

    private function productoDisponible(?int $id): ?ProductoVersion
    {
        if (! $id) {
            return null;
        }
        $version = ProductoVersion::disponiblesParaOriginacion($this->fecha->hoy())
            ->whereHas('producto', fn ($q) => $q->where('activo', true))
            ->lockForUpdate()->find($id);
        if (! $version) {
            $this->error('producto_version_id', 'Selecciona una versión de producto activa y vigente para originación.');
        }

        return $version;
    }

    private function evento(Solicitud $solicitud, User $actor, string $tipo, ActivityEvent $event, array $datos = []): void
    {
        $solicitud->eventos()->create(['actor_id' => $actor->id, 'tipo' => $tipo, 'datos' => $datos]);
        $this->actividad->log($event, $event->label(), $solicitud, ['state' => $solicitud->estado->value], $actor, sucursalId: $solicitud->sucursal_id);
    }

    private function error(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
