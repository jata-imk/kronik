<?php

namespace App\Services;

use App\Services\Credito\ConsultaCartera;
use App\Support\Decimal as D;
use Carbon\CarbonImmutable;

final class TableroService
{
    public function __construct(private ConsultaCartera $cartera, private FechaEmpresa $fecha) {}

    public function filtros(array $datos): array
    {
        $hoy = $this->fecha->hoy();

        return ['corte' => $datos['corte'] ?? $hoy->toDateString(), 'desde' => $datos['desde'] ?? $hoy->startOfMonth()->toDateString(),
            'hasta' => $datos['hasta'] ?? $hoy->toDateString(), 'detalle' => $datos['detalle'] ?? null];
    }

    public function consultar(array $alcance, array $filtros): array
    {
        $zonaHoraria = $this->fecha->zonaHoraria();
        $corte = $filtros['corte'];
        $cobertura = $this->cartera->cobertura($alcance, $corte);
        if ($cobertura['calculada_at']) {
            $cobertura['calculada_at'] = CarbonImmutable::parse($cobertura['calculada_at'], config('app.timezone'))
                ->setTimezone($zonaHoraria)->toIso8601String();
        }
        $lecturas = $this->cartera->lecturas($alcance, $corte);
        $saldos = null;
        $antiguedad = [];
        $proximos = [];
        if ($cobertura['disponible']) {
            $totales = (clone $lecturas)->selectRaw('COALESCE(SUM(l.capital), 0) AS capital, COALESCE(SUM(l.vencido), 0) AS vencido')->first();
            $saldos = ['capital' => D::round((string) $totales->capital), 'vencido' => D::round((string) $totales->vencido),
                'creditos' => (clone $lecturas)->where('l.capital', '>', 0)->count(),
                'con_atraso' => (clone $lecturas)->where('l.vencido', '>', 0)->count(),
                'clientes_atraso' => (clone $lecturas)->where('l.vencido', '>', 0)->distinct()->count('creditos.cliente_id')];
            foreach ([[1, 7], [8, 30], [31, 60], [61, 90], [91, null]] as [$min, $max]) {
                $grupo = (clone $lecturas)->where('l.vencido', '>', 0)->where('l.dias_atraso', '>=', $min)->when($max, fn ($q) => $q->where('l.dias_atraso', '<=', $max));
                $antiguedad[] = ['etiqueta' => $max ? "{$min}–{$max} días" : 'Más de 90 días', 'importe' => D::round((string) $grupo->sum('l.vencido'))];
            }
            for ($semana = 0; $semana < 4; $semana++) {
                $inicio = CarbonImmutable::parse($corte, 'UTC')->addDays(1 + $semana * 7);
                $fin = $inicio->addDays(6);
                $sumas = (clone $lecturas)->join('credito_lectura_obligaciones as o', 'o.lectura_id', '=', 'l.id')
                    ->where('o.naturaleza', 'programada')->whereBetween('o.fecha', [$inicio->toDateString(), $fin->toDateString()])
                    ->selectRaw('COALESCE(SUM(o.capital), 0) AS capital, COALESCE(SUM(o.ordinario), 0) AS intereses, COALESCE(SUM(o.total), 0) AS total')->first();
                $proximos[] = ['etiqueta' => $inicio->format('d/m').'–'.$fin->format('d/m'),
                    'capital' => D::round((string) $sumas->capital), 'intereses' => D::round((string) $sumas->intereses), 'total' => D::round((string) $sumas->total)];
            }
        }
        $cobros = $this->cartera->cobros($alcance, $filtros['desde'], $filtros['hasta']);
        $recuperado = $this->cartera->cobros($alcance, '1000-01-01', $corte)['capital'];
        $meses = [];
        $capitalMensual = [];
        for ($i = 6; $i >= 1; $i--) {
            $mes = CarbonImmutable::parse($filtros['hasta'], 'UTC')->startOfMonth()->subMonths($i);
            $fin = $mes->endOfMonth()->toDateString();
            $etiqueta = $mes->locale('es')->translatedFormat('M Y');
            $meses[] = ['etiqueta' => $etiqueta, 'cobros' => D::round((string) $this->cartera->pagos($alcance, $mes->toDateString(), $fin)->sum('p.importe')),
                'desembolsos' => D::round((string) $this->cartera->desembolsos($alcance, $mes->toDateString(), $fin)->sum('d.importe'))];
            $cierre = CarbonImmutable::parse($corte, 'UTC')->addDay()->startOfMonth()->subMonths($i);
            $finCapital = $cierre->endOfMonth()->toDateString();
            $completo = $this->cartera->cobertura($alcance, $finCapital)['disponible'];
            $capitalMensual[] = ['etiqueta' => $cierre->locale('es')->translatedFormat('M Y'), 'capital' => $completo ? D::round((string) $this->cartera->lecturas($alcance, $finCapital)->sum('l.capital')) : null];
        }

        return ['cobertura' => $cobertura, 'zona_horaria' => $zonaHoraria, 'consultada_at' => now()->toIso8601String(),
            'saldos' => $saldos, 'cobros' => $cobros, 'recuperado' => $recuperado,
            'desembolsos' => D::round((string) $this->cartera->desembolsos($alcance, $filtros['desde'], $filtros['hasta'])->sum('d.importe')),
            'antiguedad' => $antiguedad, 'proximos' => $proximos, 'movimientos_mensuales' => $meses, 'capital_mensual' => $capitalMensual];
    }

    public function detalle(array $alcance, array $filtros): mixed
    {
        $tipo = $filtros['detalle'];
        if (! $tipo) {
            return null;
        }
        if (in_array($tipo, ['cobros', 'desembolsos'], true)) {
            $pago = $tipo === 'cobros';
            $alias = $pago ? 'p' : 'd';
            $query = $pago ? $this->cartera->pagos($alcance, $filtros['desde'], $filtros['hasta'])
                : $this->cartera->desembolsos($alcance, $filtros['desde'], $filtros['hasta']);
            $query->select(['creditos.id as credito_id', 'creditos.folio', "$alias.importe", "$alias.fecha_efectiva as fecha"]);
        } else {
            if (! $this->cartera->cobertura($alcance, $filtros['corte'])['disponible']) {
                return null;
            }
            $query = $this->cartera->lecturas($alcance, $filtros['corte']);
            if ($tipo === 'proximos') {
                $fin = CarbonImmutable::parse($filtros['corte'], 'UTC')->addDays(28)->toDateString();
                $query->join('credito_lectura_obligaciones as o', 'o.lectura_id', '=', 'l.id')->where('o.naturaleza', 'programada')
                    ->where('o.fecha', '>', $filtros['corte'])->where('o.fecha', '<=', $fin)->where('o.total', '>', 0)
                    ->select(['creditos.id as credito_id', 'creditos.folio', 'o.total as importe', 'o.fecha']);
            } else {
                $campo = $tipo === 'capital' ? 'capital' : 'vencido';
                $query->where('l.'.$campo, '>', 0)->select(['creditos.id as credito_id', 'creditos.folio', 'l.'.$campo.' as importe', 'l.corte as fecha']);
            }
        }

        $listado = $query->orderByDesc('importe')->orderBy('credito_id')->paginate(25)->withQueryString();
        $listado->getCollection()->transform(fn ($fila) => [...(array) $fila, 'importe' => D::round((string) $fila->importe)]);

        return $listado;
    }
}
