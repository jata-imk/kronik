<?php

namespace App\Services\Credito;

use App\Models\Credito;
use App\Models\CreditoLectura;
use App\Services\FechaEmpresa;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Único escritor de lecturas derivadas. Siempre bajo el bloqueo de su crédito. */
final class ProyeccionCreditoService
{
    public function __construct(private LecturaCreditoService $lecturas, private ProyectorLecturaCredito $proyector, private FechaEmpresa $fecha) {}

    public function sincronizar(Credito $credito, ?string $desde = null, bool $reconstruir = false): void
    {
        DB::transaction(function () use ($credito, $desde, $reconstruir) {
            $credito = Credito::lockForUpdate()->findOrFail($credito->id);
            $cortes = $this->cortes($credito);
            foreach ($cortes as $corte) {
                if ($desde === null || $corte >= $desde) {
                    $this->actualizar($credito, $corte, $reconstruir);
                }
            }
            CreditoLectura::where('credito_id', $credito->id)->whereNotIn('corte', $cortes)->delete();
        }, attempts: 5);
    }

    public function cortes(Credito $credito): array
    {
        $hoy = $this->fecha->hoy();
        $mes = CarbonImmutable::parse($credito->fecha_desembolso->toDateString(), $this->fecha->zonaHoraria())->endOfMonth()->startOfDay();
        $cortes = [];
        while ($mes->lt($hoy)) {
            $cortes[] = $mes->toDateString();
            $mes = $mes->addMonthNoOverflow()->endOfMonth()->startOfDay();
        }
        if ($credito->fecha_desembolso->toDateString() <= $hoy->toDateString()) {
            $cortes[] = $hoy->toDateString();
        }

        return $cortes;
    }

    public function invalidar(Credito $credito): void
    {
        DB::transaction(function () use ($credito) {
            Credito::whereKey($credito->id)->lockForUpdate()->firstOrFail();
            CreditoLectura::where('credito_id', $credito->id)->update(['proyector_version' => 0]);
        });
    }

    public function diagnosticar(Credito $credito): void
    {
        DB::transaction(function () use ($credito) {
            $credito = Credito::whereKey($credito->id)->lockForUpdate()->firstOrFail();
            foreach ($this->cortes($credito) as $corte) {
                $fuente = $this->lecturas->estado($credito, $corte);
                if ($fuente->estado === null) {
                    throw ValidationException::withMessages(['pago' => 'El contrato no conserva política de mora; su lectura está identificada como no disponible.']);
                }
                $esperada = $this->proyector->calcular($fuente->estado, $fuente->cronograma, $corte);
                $actual = CreditoLectura::where('credito_id', $credito->id)->where('corte', $corte)->first();
                $coincide = $actual && $actual->fuente_hash === $fuente->fuenteHash
                    && $actual->proyector_version === ProyectorLecturaCredito::VERSION
                    && $actual->estado === $esperada['estado'] && $actual->dias_atraso === $esperada['dias_atraso']
                    && $actual->estado_hash === hash('sha256', json_encode($esperada['estado_motor'], JSON_THROW_ON_ERROR));
                foreach (['capital', 'exigible', 'vencido'] as $campo) {
                    $coincide = $coincide && $esperada[$campo] === $actual->$campo;
                }
                if ($actual) {
                    $this->lecturas->verificar($actual->estado_motor, $actual->estado_hash);
                    $guardadas = DB::table('credito_lectura_obligaciones')->where('lectura_id', $actual->id)->get()
                        ->map(fn ($fila) => (array) $fila)->all();
                    $coincide = $coincide && $this->normalizar($guardadas) === $this->normalizar($esperada['obligaciones']);
                }
                if (! $coincide) {
                    throw ValidationException::withMessages(['pago' => 'La lectura financiera requiere reconstrucción.']);
                }
            }
        });
    }

    private function normalizar(array $filas): array
    {
        $resultado = [];
        foreach ($filas as $fila) {
            $registro = [];
            foreach (['capital', 'ordinario', 'moratorio', 'comisiones', 'impuestos', 'total'] as $campo) {
                $registro[$campo] = Decimal::round((string) $fila[$campo]);
            }
            $resultado[$fila['fecha'].'|'.$fila['naturaleza']] = $registro;
        }
        ksort($resultado);

        return $resultado;
    }

    private function actualizar(Credito $credito, string $corte, bool $reconstruir): void
    {
        $fuente = $this->lecturas->estado($credito, $corte);
        if ($fuente->estado === null) {
            $lectura = CreditoLectura::updateOrCreate(['credito_id' => $credito->id, 'corte' => $corte], [
                ...$fuente->verificacion, 'ultimo_pago_id' => $fuente->ultimoPagoId, 'cronograma_version' => 1,
                'proyector_version' => ProyectorLecturaCredito::VERSION, 'fuente_hash' => $fuente->fuenteHash,
                'estado' => 'no_disponible', 'capital' => null, 'exigible' => null, 'vencido' => null,
                'dias_atraso' => null, 'estado_motor' => null, 'estado_hash' => null, 'calculada_at' => now(),
            ]);
            DB::table('credito_lectura_obligaciones')->where('lectura_id', $lectura->id)->delete();

            return;
        }
        $existente = CreditoLectura::where('credito_id', $credito->id)->where('corte', $corte)->first();
        if (! $reconstruir && $existente && $existente->fuente_hash === $fuente->fuenteHash && $existente->proyector_version === ProyectorLecturaCredito::VERSION) {
            $this->lecturas->verificar($existente->estado_motor, $existente->estado_hash);

            return;
        }
        $base = $fuente->estado;
        if (! $reconstruir) {
            $previa = CreditoLectura::where('credito_id', $credito->id)->where('corte', '<', $corte)
                ->where('fuente_hash', $fuente->fuenteHash)->where('proyector_version', ProyectorLecturaCredito::VERSION)->orderByDesc('corte')->first();
            if ($previa && $previa->estado_motor['fecha'] >= $base['fecha']) {
                $this->lecturas->verificar($previa->estado_motor, $previa->estado_hash);
                $base = $previa->estado_motor;
            }
        }
        $datos = $this->proyector->calcular($base, $fuente->cronograma, $corte);
        $obligaciones = $datos['obligaciones'];
        unset($datos['obligaciones']);
        $lectura = CreditoLectura::updateOrCreate(['credito_id' => $credito->id, 'corte' => $corte], [
            ...$datos, ...$fuente->verificacion, 'ultimo_pago_id' => $fuente->ultimoPagoId, 'cronograma_version' => $fuente->estado['cronograma_version'],
            'proyector_version' => ProyectorLecturaCredito::VERSION, 'fuente_hash' => $fuente->fuenteHash,
            'estado_hash' => hash('sha256', json_encode($datos['estado_motor'], JSON_THROW_ON_ERROR)), 'calculada_at' => now(),
        ]);
        DB::table('credito_lectura_obligaciones')->where('lectura_id', $lectura->id)->delete();
        if ($obligaciones) {
            DB::table('credito_lectura_obligaciones')->insert(array_map(fn ($fila) => ['lectura_id' => $lectura->id, ...$fila], $obligaciones));
        }
    }
}
