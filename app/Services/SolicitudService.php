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
            if ($solicitud->estado !== SolicitudEstado::Borrador) {
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
            if ($solicitud->estado !== SolicitudEstado::Borrador) {
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
            $fecha = CarbonImmutable::parse($solicitud->fecha_estimada, $this->fecha->zonaHoraria())->startOfDay();
            if ($fecha->lt($this->fecha->hoy())) {
                $this->error('fecha_estimada', 'La fecha estimada no puede ser anterior a hoy.');
            }
            $simulacion = $this->simulador->simular($producto, $solicitud->monto,
                PeriodicidadCredito::from($solicitud->periodicidad), $solicitud->plazo,
                MetodoAmortizacion::from($solicitud->metodo), $fecha);
            $snapshot = [
                'formato' => 1,
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
                'numero' => 1, 'producto_version_id' => $producto->id, 'creada_por' => $actor->id,
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
