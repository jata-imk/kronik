<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\TipoFolio;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FolioService
{
    public const MAXIMO = 999999999999999;

    public function normalizarPrefijo(?string $prefijo): string
    {
        $prefijo = strtoupper(trim($prefijo ?? ''));
        if (! preg_match('/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/D', $prefijo)
            || strlen($prefijo) > 20 || strlen(str_replace('-', '', $prefijo)) > 16) {
            $this->error('prefijo_folio', 'Usa letras A–Z, números y guiones entre grupos, hasta 20 caracteres y 16 sin guiones.');
        }

        return $prefijo;
    }

    public function formatear(string $prefijo, TipoFolio $tipo, int $numero): string
    {
        $folio = $prefijo.'-'.$tipo->codigo().'-'.str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
        if ($numero < 1 || $numero > self::MAXIMO || strlen(str_replace('-', '', $folio)) > 25) {
            $this->error('siguientes.'.$tipo->value, 'El siguiente número debe ser positivo y el folio no puede superar 25 caracteres sin guiones.');
        }

        return $folio;
    }

    public function inicializar(Sucursal $sucursal): object
    {
        return DB::transaction(function () use ($sucursal) {
            $sucursal = Sucursal::lockForUpdate()->findOrFail($sucursal->id);
            if ($sucursal->folio_ambito_id) {
                return DB::table('folio_ambitos')->where('id', $sucursal->folio_ambito_id)->lockForUpdate()->first();
            }
            // A QA reset may recreate the same logical branch. Its namespace,
            // reservations and counters survive, independently of its new ID.
            $clave = 'sucursal:'.mb_strtoupper(trim($sucursal->clave), 'UTF-8');
            $ambito = DB::table('folio_ambitos')->where('clave', $clave)->lockForUpdate()->first();
            if ($ambito && Sucursal::where('folio_ambito_id', $ambito->id)->where('id', '!=', $sucursal->id)->exists()) {
                $this->error('clave', 'Esta clave ya identifica la numeración de otra sucursal. Elige una clave nueva.');
            }
            if (! $ambito) {
                $prefijo = $this->normalizarPrefijo($sucursal->prefijo_folio ?: $sucursal->clave);
                $id = DB::table('folio_ambitos')->insertGetId(['clave' => $clave, 'tipo' => 'sucursal', 'prefijo' => $prefijo, 'version' => 0]);
                $this->reservarPrefijo($id, $prefijo);
                foreach (TipoFolio::cases() as $tipo) {
                    $siguiente = (int) ($sucursal->getAttribute('consecutivo_'.$tipo->value) ?? 1);
                    $this->formatear($prefijo, $tipo, $siguiente);
                    DB::table('folio_secuencias')->insert(['ambito_id' => $id, 'tipo' => $tipo->value, 'siguiente' => $siguiente]);
                }
                $ambito = DB::table('folio_ambitos')->find($id);
            }
            DB::table('sucursales')->where('id', $sucursal->id)->update(['folio_ambito_id' => $ambito->id, 'prefijo_folio' => $ambito->prefijo]);

            return $ambito;
        });
    }

    private function reservarPrefijo(int $ambitoId, string $prefijo): void
    {
        $normalizado = str_replace('-', '', $prefijo);
        $reserva = DB::table('folio_prefijos')->where('normalizado', $normalizado)->first();
        if ($reserva) {
            if ((int) $reserva->ambito_id !== $ambitoId) {
                $this->error('prefijo_folio', 'El prefijo, incluso sin guiones, está reservado por otra sucursal y no puede reutilizarse.');
            }

            return;
        }
        try {
            DB::table('folio_prefijos')->insert(['ambito_id' => $ambitoId, 'prefijo' => $prefijo, 'normalizado' => $normalizado]);
        } catch (UniqueConstraintViolationException) {
            $this->error('prefijo_folio', 'Otra sucursal acaba de reservar este prefijo. Actualiza la página y elige otro.');
        }
    }

    public function siguiente(TipoFolio $tipo, Sucursal $sucursal, bool $historico = false): string
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('La asignación de folios requiere la transacción del registro de negocio.');
        }
        $ambito = $this->inicializar($sucursal);
        $secuencia = DB::table('folio_secuencias')->where('ambito_id', $ambito->id)->where('tipo', $tipo->value)->lockForUpdate()->first();
        $folio = $this->formatear($ambito->prefijo, $tipo, (int) $secuencia->siguiente);
        DB::table('folio_asignaciones')->insert(['secuencia_id' => $secuencia->id, 'numero' => $secuencia->siguiente,
            'folio' => $folio, 'sucursal_id' => $sucursal->id, 'historico' => $historico, 'asignado_en' => now()]);
        DB::table('folio_secuencias')->where('id', $secuencia->id)->increment('siguiente');
        DB::table('folio_ambitos')->where('id', $ambito->id)->increment('version');

        return $folio;
    }

    public function vincular(string $folio, Model $registro): void
    {
        $actualizados = DB::table('folio_asignaciones')->where('folio', $folio)->whereNull('modelo')->update([
            'modelo' => $registro->getMorphClass(), 'registro_id' => $registro->getKey(),
        ]);
        if ($actualizados !== 1) {
            $this->error('folio', 'El folio no tiene una reserva disponible para este registro.');
        }
    }

    public function configuracion(Sucursal $sucursal): array
    {
        $ambito = $sucursal->folio_ambito_id ? DB::table('folio_ambitos')->find($sucursal->folio_ambito_id)
            : DB::table('folio_ambitos')->where('clave', 'sucursal:'.mb_strtoupper(trim($sucursal->clave), 'UTF-8'))->first();
        $prefijo = $ambito?->prefijo ?? strtoupper(trim($sucursal->prefijo_folio ?: $sucursal->clave));
        $secuencias = $ambito ? DB::table('folio_secuencias')->where('ambito_id', $ambito->id)->pluck('siguiente', 'tipo') : collect();

        return ['prefijo_folio' => $prefijo, 'version' => (int) ($ambito?->version ?? 0),
            'siguientes' => collect(TipoFolio::cases())->mapWithKeys(fn ($tipo) => [$tipo->value => (int) ($secuencias[$tipo->value] ?? $sucursal->getAttribute('consecutivo_'.$tipo->value) ?? 1)])->all()];
    }

    public function configurar(Sucursal $sucursal, array $data, User $actor): void
    {
        \Illuminate\Support\Facades\Gate::forUser($actor)->authorize('update sucursales');
        DB::transaction(function () use ($sucursal, $data, $actor) {
            $sucursal = Sucursal::lockForUpdate()->findOrFail($sucursal->id);
            $prefijo = $this->normalizarPrefijo($data['prefijo_folio']);
            $existente = $sucursal->folio_ambito_id ? DB::table('folio_ambitos')->where('id', $sucursal->folio_ambito_id)->lockForUpdate()->first()
                : DB::table('folio_ambitos')->where('clave', 'sucursal:'.mb_strtoupper(trim($sucursal->clave), 'UTF-8'))->lockForUpdate()->first();
            $prefijoAnterior = $existente?->prefijo ?? strtoupper(trim($sucursal->prefijo_folio ?: $sucursal->clave));
            if (! $existente) {
                // Repair invalid/duplicate legacy prefixes before initialization,
                // without bypassing reservations or resetting legacy counters.
                DB::table('sucursales')->where('id', $sucursal->id)->update(['prefijo_folio' => $prefijo]);
                $sucursal->refresh();
            }
            $ambito = $this->inicializar($sucursal);
            if ((int) $ambito->version !== (int) $data['version']) {
                $this->error('version', 'La numeración cambió desde que abriste el formulario. Actualiza la página antes de guardar.');
            }
            $secuencias = DB::table('folio_secuencias')->where('ambito_id', $ambito->id)->orderBy('id')->lockForUpdate()->get()->keyBy('tipo');
            $cambios = $prefijo !== $prefijoAnterior ? ['prefijo_folio'] : [];
            foreach (TipoFolio::cases() as $tipo) {
                $numero = (int) $data['siguientes'][$tipo->value];
                if ($numero < (int) $secuencias[$tipo->value]->siguiente) {
                    $this->error('siguientes.'.$tipo->value, 'No puedes reducir el siguiente número. Los folios emitidos o reservados nunca se reutilizan.');
                }
                $this->formatear($prefijo, $tipo, $numero);
                if ($numero !== (int) $secuencias[$tipo->value]->siguiente) {
                    $cambios[] = 'siguientes.'.$tipo->value;
                }
            }
            if ($cambios === []) {
                return;
            }
            $this->reservarPrefijo($ambito->id, $prefijo);
            foreach ($secuencias as $tipo => $secuencia) {
                DB::table('folio_secuencias')->where('id', $secuencia->id)->update(['siguiente' => $data['siguientes'][$tipo]]);
            }
            DB::table('folio_ambitos')->where('id', $ambito->id)->update(['prefijo' => $prefijo, 'version' => (int) $ambito->version + 1]);
            DB::table('sucursales')->where('id', $sucursal->id)->update(['prefijo_folio' => $prefijo]);
            app(ActivityLogService::class)->log(ActivityEvent::BranchFolioConfigurationUpdated, 'Numeración de sucursal actualizada', $sucursal,
                ['changed_fields' => $cambios, 'result' => 'applied'], $actor, sucursalId: $sucursal->id);
        });
    }

    public function comprobarHistoricos(): array
    {
        $prefijos = [];
        foreach (Sucursal::orderBy('id')->get() as $sucursal) {
            $prefijo = $this->normalizarPrefijo($this->configuracion($sucursal)['prefijo_folio']);
            $normalizado = str_replace('-', '', $prefijo);
            if (isset($prefijos[$normalizado])) {
                $this->error('prefijo_folio', 'Las sucursales '.$prefijos[$normalizado].' y '.$sucursal->clave.' comparten prefijo. Corrige la configuración antes de migrar.');
            }
            $prefijos[$normalizado] = $sucursal->clave;
            foreach (TipoFolio::cases() as $tipo) {
                $this->formatear($prefijo, $tipo, $this->configuracion($sucursal)['siguientes'][$tipo->value]);
            }
        }

        return collect(TipoFolio::cases())->mapWithKeys(fn ($tipo) => [$tipo->value => DB::table((new ($tipo->modelo()))->getTable())->whereNull('folio')->count()])->all();
    }

    public function completarHistoricos(): array
    {
        $pendientes = $this->comprobarHistoricos();
        DB::transaction(function () {
            foreach (Sucursal::orderBy('id')->get() as $sucursal) {
                $this->inicializar($sucursal);
            }
            foreach (TipoFolio::cases() as $tipo) {
                $clase = $tipo->modelo();
                $tabla = (new $clase)->getTable();
                while (($registros = DB::table($tabla)->whereNull('folio')->orderBy('created_at')->orderBy('id')->limit(200)->lockForUpdate()->get())->isNotEmpty()) {
                    foreach ($registros as $datos) {
                        $registro = $clase::findOrFail($datos->id);
                        $sucursal = $registro->sucursalParaFolio();
                        $folio = $this->siguiente($tipo, $sucursal, historico: true);
                        DB::table($tabla)->where('id', $datos->id)->whereNull('folio')->update(['folio' => $folio, 'folio_historico' => true, 'folio_asignado_en' => now()]);
                        $this->vincular($folio, $registro);
                        $asignacion = DB::table('folio_asignaciones')->where('folio', $folio)->value('id');
                        app(ActivityLogService::class)->log(ActivityEvent::HistoricalFolioAssigned, 'Folio asignado a registro histórico', $registro,
                            ['state' => $tipo->value, 'result' => 'assigned', 'related' => ['type' => 'folio_asignacion', 'id' => $asignacion]],
                            null, sucursalId: $sucursal->id, inheritActorContext: false);
                    }
                }
            }
        });

        return $pendientes;
    }

    private function error(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
