<?php

namespace App\Services;

use App\Enums\ProductoVersionEstado;
use App\Models\ProductoCrediticio;
use App\Models\ProductoVersion;
use App\Support\FiscalidadProducto;
use App\Support\PoliticaMora;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductoVersionService
{
    public function __construct(private readonly FechaEmpresa $fechaEmpresa) {}

    public function registrarUso(ProductoVersion $version, string $tipo, int $id): \App\Models\ProductoVersionUso
    {
        $snapshot = $version->snapshot ?? $this->snapshot($version);

        return $version->usos()->firstOrCreate(
            ['usable_type' => $tipo, 'usable_id' => $id],
            ['snapshot' => $snapshot, 'snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR))],
        );
    }

    public function crear(array $data, ?int $userId): ProductoCrediticio
    {
        return DB::transaction(function () use ($data, $userId) {
            $producto = ProductoCrediticio::create(Arr::only($data, ['clave', 'nombre', 'descripcion']));
            $version = $this->crearVersion($producto, $data['version'], $userId);
            $this->record(\App\Enums\ActivityEvent::CreditProductCreated, $version, \App\Models\User::find($userId));

            return $producto;
        });
    }

    public function actualizar(ProductoCrediticio $producto, ProductoVersion $version, array $data): ProductoCrediticio
    {
        $this->asegurarEditable($version);

        return DB::transaction(function () use ($producto, $version, $data) {
            $producto = ProductoCrediticio::whereKey($producto->id)->lockForUpdate()->firstOrFail();
            $version = ProductoVersion::query()->lockForUpdate()->findOrFail($version->id);
            $this->asegurarEditable($version);
            // Older clients omit fiscal fields; omission must not erase saved policy.
            if (! array_key_exists('fiscalidad', $data['version'])) {
                $data['version']['fiscalidad'] = $version->fiscalidad;
            }
            if (! array_key_exists('politica_mora', $data['version'])) {
                $data['version']['politica_mora'] = $version->politica_mora;
            }
            \App\Services\Credito\CapacidadesProducto::validar($data['version'], \App\Enums\VersionMotorCredito::actual(), 'version.');
            PoliticaMora::validate($data['version']);
            $before = [$this->contenidoVersion($version), $version->vigente_desde?->toDateString()];
            $productBefore = $producto->only(['clave', 'nombre', 'descripcion']);
            $anteriores = $version->comisiones()->get()->keyBy('concepto_comision_id');
            foreach ($data['version']['comisiones'] as &$comision) {
                if (! array_key_exists('evento_cobro', $comision)) {
                    $comision['evento_cobro'] = $anteriores->get($comision['concepto_comision_id'])?->evento_cobro;
                }
                if (! array_key_exists('fiscalidad', $comision)) {
                    $comision['fiscalidad'] = $anteriores->get($comision['concepto_comision_id'])?->fiscalidad;
                }
            }
            unset($comision);
            FiscalidadProducto::validate($data['version']);
            $producto->update(Arr::only($data, ['clave', 'nombre', 'descripcion']));
            $version->update($this->camposVersion($data['version']));
            $version->periodicidades()->delete();
            $version->periodicidades()->createMany($data['version']['periodicidades']);
            $version->reglas()->updateOrCreate([], $this->camposReglas($data['version']['reglas']));
            $version->comisiones()->delete();
            $version->comisiones()->createMany($this->normalizarComisiones($data['version']['comisiones']));

            $version->refresh();
            if ($before !== [$this->contenidoVersion($version), $version->vigente_desde?->toDateString()] || $productBefore !== $producto->fresh()->only(['clave', 'nombre', 'descripcion'])) {
                $this->record(\App\Enums\ActivityEvent::CreditProductUpdated, $version, auth()->user());
            }

            return $producto;
        });
    }

    public function nuevaVersion(ProductoCrediticio $producto, ProductoVersion $origen, ?int $userId): ProductoVersion
    {
        $origen->loadMissing(['periodicidades', 'reglas', 'comisiones']);
        $data = [
            ...$origen->only(['moneda', 'monto_minimo', 'monto_maximo', 'tasa_ordinaria_anual', 'tasa_moratoria_anual', 'dias_gracia_mora', 'cat_aplica', 'cat_no_aplica_motivo', 'fiscalidad', 'politica_mora']),
            'vigente_desde' => null,
            'periodicidades' => $origen->periodicidades->map->only(['periodicidad', 'plazo_minimo', 'plazo_maximo', 'plazo_predeterminado'])->all(),
            'reglas' => $origen->reglas->only(['metodos_amortizacion', 'convencion_interes', 'permite_prepago_parcial', 'permite_liquidacion_anticipada', 'monto_minimo_prepago', 'aplicacion_prepago']),
            'comisiones' => $origen->comisiones->map->only(['concepto_comision_id', 'tipo_importe', 'importe', 'base_calculo', 'momento_cobro', 'evento_cobro', 'modalidad_cobro', 'obligatoria', 'fiscalidad'])->all(),
        ];

        return DB::transaction(function () use ($producto, $data, $userId) {
            $version = $this->crearVersion($producto, $data, $userId);
            $this->record(\App\Enums\ActivityEvent::CreditProductVersioned, $version, \App\Models\User::find($userId));

            return $version;
        });
    }

    public function activar(ProductoVersion $version, string $vigenteDesde): ProductoVersion
    {
        return DB::transaction(function () use ($version, $vigenteDesde) {
            ProductoCrediticio::whereKey($version->producto_crediticio_id)->lockForUpdate()->firstOrFail();
            $version = ProductoVersion::query()->lockForUpdate()->findOrFail($version->id);
            $this->asegurarEditable($version);
            $actor = auth()->user();
            $snapshot = $this->snapshot($version);
            $fechaVigencia = CarbonImmutable::parse($vigenteDesde, $this->fechaEmpresa->zonaHoraria())->startOfDay();
            $programada = $this->fechaEmpresa->hoy()->lt($fechaVigencia);
            if (! $programada) {
                $this->retirarActivaAnterior($version, $actor);
            }
            $version->update([
                'programada_por' => $programada ? $actor?->id : null,
                'programada_team_id' => $programada ? $actor?->current_team_id : null,
                'programada_sucursal_id' => $programada ? $actor?->current_sucursal_id : null,
                'estado' => $programada ? ProductoVersionEstado::Programada : ProductoVersionEstado::Activa,
                'vigente_desde' => $fechaVigencia->toDateString(),
                'activada_en' => $programada ? null : now(),
                'snapshot' => $snapshot,
                'snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)),
            ]);

            $this->record($programada ? \App\Enums\ActivityEvent::CreditProductActivationScheduled : \App\Enums\ActivityEvent::CreditProductActivated, $version, $actor);

            return $version->refresh();
        });
    }

    public function retirar(ProductoVersion $version): ProductoVersion
    {
        return DB::transaction(function () use ($version) {
            ProductoCrediticio::whereKey($version->producto_crediticio_id)->lockForUpdate()->firstOrFail();
            $version = ProductoVersion::whereKey($version->id)->lockForUpdate()->firstOrFail();
            if (! in_array($version->estado, [ProductoVersionEstado::Activa, ProductoVersionEstado::Programada], true)) {
                throw ValidationException::withMessages(['version' => 'Solo puede retirarse una versión activa o programada.']);
            }
            $version->update(['estado' => ProductoVersionEstado::Retirada, 'retirada_en' => now()]);
            $this->record(\App\Enums\ActivityEvent::CreditProductRetired, $version, auth()->user());

            return $version;
        });
    }

    public function activarProgramadas(): int
    {
        $count = 0;
        ProductoVersion::query()->where('estado', ProductoVersionEstado::Programada)
            ->whereDate('vigente_desde', '<=', $this->fechaEmpresa->hoy()->toDateString())
            ->orderBy('vigente_desde')->orderBy('id')->each(function ($candidate) use (&$count) {
                DB::transaction(function () use ($candidate, &$count) {
                    ProductoCrediticio::whereKey($candidate->producto_crediticio_id)->lockForUpdate()->firstOrFail();
                    $version = ProductoVersion::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                    if ($version->estado !== ProductoVersionEstado::Programada || $version->vigente_desde->toDateString() > $this->fechaEmpresa->hoy()->toDateString()) {
                        return;
                    }
                    $context = [$version->programada_team_id, $version->programada_sucursal_id, $version->programada_por];
                    $this->retirarActivaAnterior($version, null, $context);
                    $version->update(['estado' => ProductoVersionEstado::Activa, 'activada_en' => now()]);
                    $this->record(\App\Enums\ActivityEvent::CreditProductActivated, $version, null, $context);
                    $count++;
                });
            });

        return $count;
    }

    private function crearVersion(ProductoCrediticio $producto, array $data, ?int $userId): ProductoVersion
    {
        \App\Services\Credito\CapacidadesProducto::validar($data, \App\Enums\VersionMotorCredito::actual(), 'version.');
        FiscalidadProducto::validate($data);
        PoliticaMora::validate($data);
        $numero = ((int) $producto->versiones()->lockForUpdate()->max('numero')) + 1;
        $version = $producto->versiones()->create([...$this->camposVersion($data), 'numero' => $numero, 'creada_por' => $userId]);
        $version->periodicidades()->createMany($data['periodicidades']);
        $version->reglas()->create($this->camposReglas($data['reglas']));
        $version->comisiones()->createMany($this->normalizarComisiones($data['comisiones'] ?? []));

        return $version;
    }

    private function camposVersion(array $data): array
    {
        return Arr::only($data, ['moneda', 'monto_minimo', 'monto_maximo', 'tasa_ordinaria_anual', 'tasa_moratoria_anual', 'dias_gracia_mora', 'cat_aplica', 'cat_no_aplica_motivo', 'vigente_desde', 'fiscalidad', 'politica_mora']);
    }

    /** @param array<int, array<string, mixed>> $comisiones */
    private function normalizarComisiones(array $comisiones): array
    {
        return array_map(function (array $comision): array {
            $momentoNormal = in_array($comision['momento_cobro'] ?? null, ['inicio', 'firma', 'desembolso_descuento', 'cada_pago'], true);
            $comision['incluye_cat'] = (bool) ($comision['obligatoria'] ?? false) && $momentoNormal;

            return $comision;
        }, $comisiones);
    }

    private function camposReglas(array $data): array
    {
        return [...Arr::only($data, ['metodos_amortizacion', 'permite_prepago_parcial', 'permite_liquidacion_anticipada', 'monto_minimo_prepago', 'aplicacion_prepago']), 'convencion_interes' => \App\Enums\ConvencionInteres::desdeReglas($data)->value, 'base_moratoria' => 'capital_vencido', 'ajuste_dia_inhabil' => 'sin_ajuste', 'redondeo' => 'half_up'];
    }

    private function asegurarEditable(ProductoVersion $version): void
    {
        if (! $version->esEditable()) {
            throw ValidationException::withMessages(['version' => 'Esta versión ya fue activada o utilizada. Cree una nueva versión para cambiar sus condiciones.']);
        }
    }

    private function snapshot(ProductoVersion $version): array
    {
        $version->loadMissing(['periodicidades', 'reglas', 'comisiones.concepto']);

        \App\Services\Credito\CapacidadesProducto::validar(\App\Services\Credito\CapacidadesProducto::condiciones($version), \App\Enums\VersionMotorCredito::actual());

        return $this->contenidoVersion($version);
    }

    private function contenidoVersion(ProductoVersion $version): array
    {
        $version->loadMissing(['periodicidades', 'reglas', 'comisiones.concepto']);

        return json_decode(json_encode($version->only(['numero', 'moneda', 'monto_minimo', 'monto_maximo', 'tasa_ordinaria_anual', 'tasa_moratoria_anual', 'dias_gracia_mora', 'cat_aplica', 'cat_no_aplica_motivo', 'fiscalidad', 'politica_mora']) + [
            'motor_calculo_version' => \App\Enums\VersionMotorCredito::actual()->value,
            'periodicidades' => $version->periodicidades->map->only(['periodicidad', 'plazo_minimo', 'plazo_maximo', 'plazo_predeterminado'])->all(),
            'reglas' => $version->reglas?->only(['metodos_amortizacion', 'convencion_interes', 'base_moratoria', 'permite_prepago_parcial', 'permite_liquidacion_anticipada', 'monto_minimo_prepago', 'aplicacion_prepago', 'ajuste_dia_inhabil', 'redondeo']),
            'comisiones' => $version->comisiones->map(fn ($c) => [...$c->only(['tipo_importe', 'importe', 'base_calculo', 'momento_cobro', 'evento_cobro', 'modalidad_cobro', 'obligatoria', 'incluye_cat', 'fiscalidad']), 'concepto' => $c->concepto->only(['clave', 'nombre'])])->all(),
        ], JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private function retirarActivaAnterior(ProductoVersion $version, ?\App\Models\User $actor, ?array $context = null): void
    {
        foreach (ProductoVersion::query()->where('producto_crediticio_id', $version->producto_crediticio_id)
            ->where('estado', ProductoVersionEstado::Activa)->whereKeyNot($version->id)->lockForUpdate()->get() as $previous) {
            $previous->update(['estado' => ProductoVersionEstado::Retirada, 'retirada_en' => now()]);
            $this->record(\App\Enums\ActivityEvent::CreditProductRetired, $previous, $actor, $context);
        }
    }

    private function record(\App\Enums\ActivityEvent $event, ProductoVersion $version, ?\App\Models\User $actor, ?array $context = null): void
    {
        $version->refresh();
        $metadata = ['changed_fields' => ['version'], 'state' => $version->estado->value, 'result' => 'applied',
            'related' => ['type' => 'producto_version', 'id' => $version->id]];
        if ($context !== null && $context[2] !== null) {
            $metadata['related'] = ['type' => 'iniciador', 'id' => $context[2]];
        }
        // Scheduled initiator is retained on the version, including an unknown historical context.
        app(ActivityLogService::class)->log($event, $event->label(), $context !== null ? $version : $version->producto, $metadata, $actor,
            $context !== null ? $context[0] : $actor?->current_team_id,
            $context !== null ? $context[1] : $actor?->current_sucursal_id, inheritActorContext: false);
    }
}
