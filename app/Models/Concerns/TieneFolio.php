<?php

namespace App\Models\Concerns;

use App\Enums\TipoFolio;
use App\Models\Sucursal;
use App\Services\FolioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait TieneFolio
{
    public function initializeTieneFolio(): void
    {
        $this->mergeCasts(['folio_historico' => 'boolean', 'folio_asignado_en' => 'datetime']);
    }

    public function save(array $options = [])
    {
        if (! $this->exists && DB::transactionLevel() === 0) {
            $attributes = $this->getAttributes();
            $original = $this->getRawOriginal();

            return DB::transaction(function () use ($options, $attributes, $original) {
                // A deadlock may occur after INSERT but before the ledger is
                // linked. Restore the new instance as well as the DB rollback.
                $this->exists = false;
                $this->wasRecentlyCreated = false;
                $this->original = $original;
                $this->setRawAttributes($attributes);

                return parent::save($options);
            }, attempts: 5);
        }

        return parent::save($options);
    }

    public function tipoFolio(): TipoFolio
    {
        return match (static::class) {
            \App\Models\Solicitud::class => TipoFolio::Solicitud,
            \App\Models\SolicitudPaquete::class => TipoFolio::Contrato,
            \App\Models\Credito::class => TipoFolio::Credito,
            \App\Models\CreditoPago::class => TipoFolio::Recibo,
        };
    }

    public function sucursalParaFolio(): Sucursal
    {
        $sucursalId = match ($this->tipoFolio()) {
            TipoFolio::Contrato => $this->solicitud()->value('sucursal_id'),
            TipoFolio::Recibo => $this->credito()->value('sucursal_id'),
            default => $this->sucursal_id,
        };

        return Sucursal::findOrFail($sucursalId);
    }

    protected static function bootTieneFolio(): void
    {
        static::creating(function ($registro) {
            $service = app(FolioService::class);
            $tipo = $registro->tipoFolio();
            $sucursal = $registro->sucursalParaFolio();
            $registro->folio ??= $service->siguiente($tipo, $sucursal);
            $reserva = DB::table('folio_asignaciones as a')->join('folio_secuencias as s', 's.id', '=', 'a.secuencia_id')
                ->where('a.folio', $registro->folio)->whereNull('a.modelo')->where('s.tipo', $tipo->value)
                ->where('a.sucursal_id', $sucursal->id)->select('a.*')->lockForUpdate()->first();
            if (! $reserva) {
                throw ValidationException::withMessages(['folio' => 'El folio debe asignarse automáticamente para este tipo y sucursal.']);
            }
            $registro->folio_asignado_en = $reserva->asignado_en;
            $registro->folio_historico = false;
        });
        static::created(fn ($registro) => app(FolioService::class)->vincular($registro->folio, $registro));
        static::updating(function ($registro) {
            if ($registro->isDirty(['folio', 'folio_asignado_en', 'folio_historico'])) {
                throw ValidationException::withMessages(['folio' => 'El folio y su evidencia de asignación son inmutables.']);
            }
        });
        static::deleting(function ($registro) {
            if ($registro->folio !== null) {
                throw ValidationException::withMessages(['folio' => 'Conserva el registro y cancélalo mediante su flujo. Los folios emitidos nunca se borran ni reutilizan.']);
            }
        });
    }
}
