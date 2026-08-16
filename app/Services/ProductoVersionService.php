<?php

namespace App\Services;

use App\Enums\ProductoVersionEstado;
use App\Models\ProductoCrediticio;
use App\Models\ProductoVersion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductoVersionService
{
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
            $this->crearVersion($producto, $data['version'], $userId);

            return $producto;
        });
    }

    public function actualizar(ProductoCrediticio $producto, ProductoVersion $version, array $data): ProductoCrediticio
    {
        $this->asegurarEditable($version);

        return DB::transaction(function () use ($producto, $version, $data) {
            $producto->update(Arr::only($data, ['clave', 'nombre', 'descripcion']));
            $version->update($this->camposVersion($data['version']));
            $version->periodicidades()->delete();
            $version->periodicidades()->createMany($data['version']['periodicidades']);
            $version->reglas()->updateOrCreate([], $this->camposReglas($data['version']['reglas']));
            $version->comisiones()->delete();
            $version->comisiones()->createMany($data['version']['comisiones']);

            return $producto;
        });
    }

    public function nuevaVersion(ProductoCrediticio $producto, ProductoVersion $origen, ?int $userId): ProductoVersion
    {
        $origen->loadMissing(['periodicidades', 'reglas', 'comisiones']);
        $data = [
            ...$origen->only(['moneda', 'monto_minimo', 'monto_maximo', 'tasa_ordinaria_anual', 'tasa_moratoria_anual', 'dias_gracia_mora', 'cat_aplica', 'cat_no_aplica_motivo']),
            'vigente_desde' => null,
            'periodicidades' => $origen->periodicidades->map->only(['periodicidad', 'plazo_minimo', 'plazo_maximo', 'plazo_predeterminado'])->all(),
            'reglas' => $origen->reglas->only(['metodos_amortizacion', 'permite_prepago_parcial', 'permite_liquidacion_anticipada', 'monto_minimo_prepago', 'aplicacion_prepago']),
            'comisiones' => $origen->comisiones->map->only(['concepto_comision_id', 'tipo_importe', 'importe', 'base_calculo', 'momento_cobro', 'modalidad_cobro', 'obligatoria', 'incluye_cat'])->all(),
        ];

        return DB::transaction(fn () => $this->crearVersion($producto, $data, $userId));
    }

    public function activar(ProductoVersion $version, string $vigenteDesde): ProductoVersion
    {
        return DB::transaction(function () use ($version, $vigenteDesde) {
            $version = ProductoVersion::query()->lockForUpdate()->findOrFail($version->id);
            $this->asegurarEditable($version);
            $snapshot = $this->snapshot($version);
            $programada = now()->startOfDay()->lt($vigenteDesde);
            if (! $programada) {
                $this->retirarActivaAnterior($version);
            }
            $version->update([
                'estado' => $programada ? ProductoVersionEstado::Programada : ProductoVersionEstado::Activa,
                'vigente_desde' => $vigenteDesde,
                'activada_en' => $programada ? null : now(),
                'snapshot' => $snapshot,
                'snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)),
            ]);

            return $version->refresh();
        });
    }

    public function retirar(ProductoVersion $version): ProductoVersion
    {
        if (! in_array($version->estado, [ProductoVersionEstado::Activa, ProductoVersionEstado::Programada], true)) {
            throw ValidationException::withMessages(['version' => 'Solo puede retirarse una versión activa o programada.']);
        }
        $version->update(['estado' => ProductoVersionEstado::Retirada, 'retirada_en' => now()]);

        return $version;
    }

    public function activarProgramadas(): int
    {
        $count = 0;
        ProductoVersion::query()->where('estado', ProductoVersionEstado::Programada)->whereDate('vigente_desde', '<=', today())->orderBy('vigente_desde')->each(function ($version) use (&$count) {
            DB::transaction(function () use ($version, &$count) {
                $this->retirarActivaAnterior($version);
                $version->update(['estado' => ProductoVersionEstado::Activa, 'activada_en' => now()]);
                $count++;
            });
        });

        return $count;
    }

    private function crearVersion(ProductoCrediticio $producto, array $data, ?int $userId): ProductoVersion
    {
        $numero = ((int) $producto->versiones()->lockForUpdate()->max('numero')) + 1;
        $version = $producto->versiones()->create([...$this->camposVersion($data), 'numero' => $numero, 'creada_por' => $userId]);
        $version->periodicidades()->createMany($data['periodicidades']);
        $version->reglas()->create($this->camposReglas($data['reglas']));
        $version->comisiones()->createMany($data['comisiones'] ?? []);

        return $version;
    }

    private function camposVersion(array $data): array
    {
        return Arr::only($data, ['moneda', 'monto_minimo', 'monto_maximo', 'tasa_ordinaria_anual', 'tasa_moratoria_anual', 'dias_gracia_mora', 'cat_aplica', 'cat_no_aplica_motivo', 'vigente_desde']);
    }

    private function camposReglas(array $data): array
    {
        return [...Arr::only($data, ['metodos_amortizacion', 'permite_prepago_parcial', 'permite_liquidacion_anticipada', 'monto_minimo_prepago', 'aplicacion_prepago']), 'convencion_interes' => 'dias_reales_360', 'base_moratoria' => 'capital_vencido', 'ajuste_dia_inhabil' => 'sin_ajuste', 'redondeo' => 'half_up'];
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

        return json_decode(json_encode($version->only(['numero', 'moneda', 'monto_minimo', 'monto_maximo', 'tasa_ordinaria_anual', 'tasa_moratoria_anual', 'dias_gracia_mora', 'cat_aplica', 'cat_no_aplica_motivo']) + [
            'periodicidades' => $version->periodicidades->map->only(['periodicidad', 'plazo_minimo', 'plazo_maximo', 'plazo_predeterminado'])->all(),
            'reglas' => $version->reglas?->only(['metodos_amortizacion', 'convencion_interes', 'base_moratoria', 'permite_prepago_parcial', 'permite_liquidacion_anticipada', 'monto_minimo_prepago', 'aplicacion_prepago', 'ajuste_dia_inhabil', 'redondeo']),
            'comisiones' => $version->comisiones->map(fn ($c) => [...$c->only(['tipo_importe', 'importe', 'base_calculo', 'momento_cobro', 'modalidad_cobro', 'obligatoria', 'incluye_cat']), 'concepto' => $c->concepto->only(['clave', 'nombre'])])->all(),
        ], JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private function retirarActivaAnterior(ProductoVersion $version): void
    {
        ProductoVersion::query()->where('producto_crediticio_id', $version->producto_crediticio_id)->where('estado', ProductoVersionEstado::Activa)->whereKeyNot($version->id)->update(['estado' => ProductoVersionEstado::Retirada, 'retirada_en' => now()]);
    }
}
