<?php

namespace App\Services\Credito;

use App\Enums\ActivityEvent;
use App\Models\CalendarioCrediticioVersion;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class CalendarioCrediticioService
{
    public const CONSULTAR = 'read calendar productos-crediticios';

    public const EDITAR = 'manage calendar productos-crediticios';

    public const CONSERVAR = 'sin_ajuste';

    public const AJUSTAR = 'siguiente_habil';

    public function __construct(private readonly ActivityLogService $actividad) {}

    public function vigente(): ?CalendarioCrediticioVersion
    {
        return CalendarioCrediticioVersion::whereNotNull('publicada_en')->orderByDesc('orden_publicacion')->first();
    }

    public function guardar(?CalendarioCrediticioVersion $version, array $datos, User $actor): CalendarioCrediticioVersion
    {
        Gate::forUser($actor)->authorize(self::CONSULTAR);
        Gate::forUser($actor)->authorize(self::EDITAR);

        return DB::transaction(function () use ($version, $datos, $actor) {
            $actual = $version ? $this->borrador($version, $datos['revision'] ?? 0) : new CalendarioCrediticioVersion(['revision' => 0]);
            $configuracion = $datos['configuracion'];
            $configuracion['semana_habil'] = array_map('intval', $configuracion['semana_habil']);
            sort($configuracion['semana_habil']);
            $configuracion['excepciones'] = array_map(fn (array $dia) => [...$dia, 'habil' => (bool) $dia['habil']], $configuracion['excepciones']);
            foreach (['feriados', 'excepciones'] as $campo) {
                $configuracion[$campo] ??= [];
                usort($configuracion[$campo], fn (array $a, array $b) => $a['fecha'] <=> $b['fecha']);
            }
            $configuracion = $this->ordenarClaves($configuracion);
            if ($actual->exists && $this->ordenarClaves($actual->configuracion) === $configuracion) {
                return $actual;
            }
            $actual->fill(['configuracion' => $configuracion, 'revision' => $actual->revision + 1])->save();
            $this->actividad->log(ActivityEvent::CreditCalendarDraftSaved, 'Borrador del calendario guardado', $actual,
                ['result' => 'applied', 'changed_fields' => ['configuracion']], $actor);

            return $actual;
        });
    }

    public function publicar(CalendarioCrediticioVersion $version, int $revision, User $actor): void
    {
        abort_unless($actor->is_super_admin, 403);

        DB::transaction(function () use ($version, $revision, $actor) {
            CalendarioCrediticioVersion::orderBy('id')->lockForUpdate()->firstOrFail();
            $actual = CalendarioCrediticioVersion::lockForUpdate()->findOrFail($version->id);
            if ($actual->publicada_en && $actual->revision === $revision) {
                return;
            }
            $this->verificarRevision($actual, $revision);
            $actual->fill(['publicada_en' => now(), 'orden_publicacion' => (CalendarioCrediticioVersion::max('orden_publicacion') ?? 0) + 1, 'huella' => hash('sha256', json_encode($this->ordenarClaves($actual->configuracion), JSON_THROW_ON_ERROR))])->save();
            $this->actividad->log(ActivityEvent::CreditCalendarPublished, 'Calendario crediticio publicado', $actual,
                ['result' => 'applied', 'changed_fields' => ['publicada_en']], $actor);
        });
    }

    public function clasificar(array $configuracion, CarbonImmutable $fecha): array
    {
        $dia = $fecha->toDateString();
        if ($dia < $configuracion['desde'] || $dia > $configuracion['hasta']) {
            return ['tipo' => 'fuera_de_cobertura', 'habil' => null, 'motivo' => 'Fuera de cobertura revisada'];
        }

        $excepcion = collect($configuracion['excepciones'])->firstWhere('fecha', $dia);
        if ($excepcion !== null) {
            return ['tipo' => $excepcion['habil'] ? 'apertura' : 'cierre', 'habil' => $excepcion['habil'], 'motivo' => $excepcion['nombre']];
        }

        $feriado = collect($configuracion['feriados'])->firstWhere('fecha', $dia);
        if ($feriado !== null) {
            return ['tipo' => 'feriado', 'habil' => false, 'motivo' => $feriado['nombre']];
        }

        $habil = in_array($fecha->dayOfWeekIso, $configuracion['semana_habil']);

        return ['tipo' => $habil ? 'habil' : 'descanso_semanal', 'habil' => $habil, 'motivo' => $habil ? 'Día hábil' : 'Descanso semanal'];
    }

    public function ajustar(CalendarioCrediticioVersion $version, array $nominales): array
    {
        $filas = [];
        $anterior = null;
        foreach ($nominales as $nominal) {
            $fecha = $nominal;
            $inhabiles = [];
            do {
                $dia = $this->clasificar($version->configuracion, $fecha);
                $civil = $fecha->toDateString();
                if ($dia['habil'] === null) {
                    throw ValidationException::withMessages(['calendario' => "Falta cobertura revisada para el {$civil}. Solicita que se publique una ampliación del calendario."]);
                }
                if ($dia['habil']) {
                    break;
                }
                $inhabiles[] = ['fecha' => $civil, ...$dia];
                $fecha = $fecha->addDay();
            } while (true);

            if ($anterior !== null && $civil <= $anterior['fecha']) {
                throw ValidationException::withMessages(['calendario' => "Los vencimientos {$anterior['fecha_original']} y {$nominal->toDateString()} coinciden al ajustarse al {$civil}. Revisa el calendario o la periodicidad."]);
            }
            $filas[] = $anterior = ['fecha_original' => $nominal->toDateString(), 'fecha' => $civil, 'inhabiles' => $inhabiles];
        }

        return ['version_id' => $version->id, 'huella' => $version->huella, 'fechas' => $filas];
    }

    private function ordenarClaves(array $datos): array
    {
        if (! array_is_list($datos)) {
            ksort($datos);
        }

        return array_map(fn ($valor) => is_array($valor) ? $this->ordenarClaves($valor) : $valor, $datos);
    }

    private function borrador(CalendarioCrediticioVersion $version, int $revision): CalendarioCrediticioVersion
    {
        $actual = CalendarioCrediticioVersion::lockForUpdate()->findOrFail($version->id);
        $this->verificarRevision($actual, $revision);

        return $actual;
    }

    private function verificarRevision(CalendarioCrediticioVersion $version, int $revision): void
    {
        if ($version->publicada_en || $version->revision !== $revision) {
            throw ValidationException::withMessages(['revision' => 'La versión cambió o ya fue publicada. Vuelve a abrirla antes de continuar.']);
        }
    }
}
