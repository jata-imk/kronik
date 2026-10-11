<?php

namespace App\Services\SICs;

use App\Support\Decimal as D;

/** Read-only summaries of reported facts. Never mixes currencies or invents missing balances. */
final class SicResumen
{
    public function construir(array $resultado): array
    {
        $secciones = $resultado['secciones'] ?? [];
        $monedas = [];
        $tipos = [];
        $atraso = ['Con saldo vencido' => 0, 'Sin saldo vencido' => 0, 'Saldo vencido no informado' => 0];
        foreach ($secciones['creditos'] ?? [] as $i => $c) {
            if (! is_array($c)) {
                continue;
            }
            $tipo = is_string($c['tipoCredito'] ?? null) ? $c['tipoCredito'] : 'Sin tipo informado';
            $tipos[$tipo] = ($tipos[$tipo] ?? 0) + 1;
            $vencido = $this->decimal($c['saldoVencido'] ?? null);
            $atraso[$vencido === null ? 'Saldo vencido no informado' : (D::compare($vencido, '0') > 0 ? 'Con saldo vencido' : 'Sin saldo vencido')]++;
            $moneda = is_string($c['claveUnidadMonetaria'] ?? null) ? $c['claveUnidadMonetaria'] : 'Sin moneda informada';
            $actual = $this->decimal($c['saldoActual'] ?? null);
            $monedas[$moneda] ??= ['moneda' => $moneda, 'saldo_actual' => null, 'saldo_vencido' => null,
                'cuentas_con_saldo' => 0, 'cuentas_con_vencido' => 0, 'cuentas' => []];
            foreach (['saldo_actual' => $actual, 'saldo_vencido' => $vencido] as $key => $valor) {
                if ($valor !== null) {
                    $monedas[$moneda][$key] = D::add($monedas[$moneda][$key] ?? '0.00', $valor, 2);
                }
            }
            if ($actual !== null) {
                $monedas[$moneda]['cuentas_con_saldo']++;
            }
            if ($vencido !== null) {
                $monedas[$moneda]['cuentas_con_vencido']++;
            }
            $monedas[$moneda]['cuentas'][] = ['etiqueta' => 'Cuenta '.($i + 1).' · '.($c['nombreOtorgante'] ?? 'Otorgante no informado'), 'actual' => $actual, 'vencido' => $vencido];
        }
        $meses = [];
        $sinFecha = 0;
        foreach ($secciones['consultas'] ?? [] as $c) {
            $fecha = is_array($c) ? ($c['fechaConsulta'] ?? null) : null;
            if (is_string($fecha) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $fecha, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                $mes = substr($fecha, 0, 7);
                $meses[$mes] = ($meses[$mes] ?? 0) + 1;
            } else {
                $sinFecha++;
            }
        }
        ksort($meses);
        $filas = fn (array $valores) => array_map(fn ($key, $value) => ['etiqueta' => (string) $key, 'cantidad' => $value], array_keys($valores), $valores);

        return ['conteos' => array_map('count', $secciones), 'monedas' => array_values($monedas), 'tipos' => $filas($tipos),
            'atraso' => $tipos ? $filas($atraso) : [], 'consultas_meses' => $filas($meses), 'consultas_sin_fecha' => $sinFecha];
    }

    private function decimal(mixed $valor): ?string
    {
        if (! is_int($valor) && ! is_float($valor) && ! is_string($valor)) {
            return null;
        }
        $text = (string) $valor;

        return preg_match('/^-?\d{1,14}(\.\d{1,8})?$/D', $text) ? D::round($text) : null;
    }
}
