<?php

namespace App\Services\Credito;

use App\Models\Credito;
use App\Services\ConsultaSucursal;
use Illuminate\Database\Query\Builder;

/** Solo lectura relacional: no carga snapshots ni ejecuta el motor. Autoriza el llamador. */
final class ConsultaCartera
{
    public function creditos(array $alcance): Builder
    {
        return app(ConsultaSucursal::class)->aplicar(Credito::query(), $alcance, 'creditos.sucursal_id')->toBase();
    }

    public function lecturas(array $alcance, string $corte): Builder
    {
        return $this->creditos($alcance)->where('creditos.fecha_desembolso', '<=', $corte)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('credito_capturas_historicas as captura')
                ->whereColumn('captura.credito_id', 'creditos.id')->whereNull('captura.completada_en'))
            ->join('credito_lecturas as l', fn ($j) => $j->on('l.credito_id', '=', 'creditos.id')->where('l.corte', $corte))
            ->join('credito_cronogramas as original', fn ($j) => $j->on('original.credito_id', '=', 'creditos.id')->where('original.version', 1))
            ->join('credito_cronogramas as calendario', fn ($j) => $j->on('calendario.credito_id', '=', 'creditos.id')->on('calendario.version', '=', 'l.cronograma_version'))
            ->leftJoin('credito_pagos as fuente_pago', 'fuente_pago.id', '=', 'l.ultimo_pago_id')
            ->where('l.proyector_version', ProyectorLecturaCredito::VERSION)
            ->whereIn('l.estado', ['activo', 'liquidado'])
            ->whereColumn('l.condiciones_hash', 'creditos.condiciones_hash')
            ->whereColumn('l.original_hash', 'original.snapshot_hash')
            ->whereColumn('l.cronograma_hash', 'calendario.snapshot_hash')
            ->where(fn ($q) => $q->whereNull('l.ultimo_pago_id')->orWhereColumn('l.pago_hash', 'fuente_pago.snapshot_hash'))
            ->whereRaw('COALESCE(l.ultimo_pago_id, 0) = COALESCE((SELECT MAX(p.id) FROM credito_pagos p WHERE p.credito_id = creditos.id AND p.fecha_efectiva <= ?), 0)', [$corte]);
    }

    public function cobertura(array $alcance, string $corte): array
    {
        $inventario = $this->creditos($alcance)->where('creditos.fecha_desembolso', '<=', $corte)
            ->leftJoin('credito_capturas_historicas as captura', 'captura.credito_id', '=', 'creditos.id')
            ->selectRaw('COUNT(*) AS creditos, COALESCE(SUM(CASE WHEN captura.id IS NOT NULL AND captura.completada_en IS NULL THEN 1 ELSE 0 END), 0) AS pendientes')->first();
        $esperadas = (int) $inventario->creditos;
        $lecturas = $this->lecturas($alcance, $corte);
        $validas = (clone $lecturas)->count();

        return ['disponible' => $validas === $esperadas, 'creditos' => $esperadas, 'verificados' => $validas, 'capturas_pendientes' => (int) $inventario->pendientes,
            'calculada_at' => (clone $lecturas)->min('l.calculada_at')];
    }

    public function pagos(array $alcance, string $desde, string $hasta): Builder
    {
        return $this->creditos($alcance)->join('credito_pagos as p', 'p.credito_id', '=', 'creditos.id')
            ->whereIn('p.tipo', ['pago', 'prepago', 'liquidacion'])->whereBetween('p.fecha_efectiva', [$desde, $hasta])
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('credito_pagos as reverso')->whereColumn('reverso.reversa_de', 'p.id'));
    }

    public function cobros(array $alcance, string $desde, string $hasta): array
    {
        $filas = $this->pagos($alcance, $desde, $hasta)
            ->join('credito_movimientos as m', 'm.credito_pago_id', '=', 'p.id')
            ->whereIn('m.tipo', ['pago_concepto', 'pago_impuesto'])
            ->selectRaw("CASE WHEN m.tipo = 'pago_impuesto' THEN 'impuestos' WHEN m.concepto LIKE '%:capital' THEN 'capital' WHEN m.concepto LIKE '%:ordinario' THEN 'ordinario' WHEN m.concepto LIKE '%:moratorio' THEN 'moratorio' ELSE 'comisiones' END AS grupo, -SUM(m.importe) AS total")
            ->groupBy('grupo')->get();
        $resultado = array_fill_keys(['capital', 'ordinario', 'moratorio', 'comisiones', 'impuestos'], '0.00');
        foreach ($filas as $fila) {
            $resultado[$fila->grupo] = \App\Support\Decimal::round((string) $fila->total);
        }
        $resultado['total'] = array_reduce($resultado, fn ($s, $v) => \App\Support\Decimal::add($s, $v, 2), '0.00');

        return $resultado;
    }

    public function desembolsos(array $alcance, string $desde, string $hasta): Builder
    {
        return $this->creditos($alcance)->join('credito_desembolsos as d', 'd.credito_id', '=', 'creditos.id')
            ->whereBetween('d.fecha_efectiva', [$desde, $hasta]);
    }
}
