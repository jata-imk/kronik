<?php

namespace App\Models;

use App\Enums\ProductoVersionEstado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ProductoVersion extends Model
{
    private const IMMUTABLE_FIELDS = [
        'moneda', 'monto_minimo', 'monto_maximo', 'tasa_ordinaria_anual',
        'tasa_moratoria_anual', 'dias_gracia_mora', 'cat_aplica',
        'cat_no_aplica_motivo', 'vigente_desde', 'snapshot', 'snapshot_hash',
    ];

    protected $table = 'producto_versiones';

    protected $guarded = [];

    protected $casts = [
        'estado' => ProductoVersionEstado::class,
        'monto_minimo' => 'decimal:4', 'monto_maximo' => 'decimal:4',
        'tasa_ordinaria_anual' => 'decimal:8', 'tasa_moratoria_anual' => 'decimal:8',
        'cat_aplica' => 'boolean', 'vigente_desde' => 'date', 'activada_en' => 'datetime', 'retirada_en' => 'datetime', 'snapshot' => 'array',
    ];

    public function producto()
    {
        return $this->belongsTo(ProductoCrediticio::class, 'producto_crediticio_id');
    }

    public function periodicidades()
    {
        return $this->hasMany(ProductoVersionPeriodicidad::class);
    }

    public function reglas()
    {
        return $this->hasOne(ProductoVersionRegla::class);
    }

    public function comisiones()
    {
        return $this->hasMany(ProductoVersionComision::class);
    }

    public function usos()
    {
        return $this->hasMany(ProductoVersionUso::class);
    }

    public function esEditable(): bool
    {
        return $this->estado === ProductoVersionEstado::Borrador && ! $this->usos()->exists();
    }

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            $eraEditable = $version->getRawOriginal('estado') === ProductoVersionEstado::Borrador->value && ! $version->usos()->exists();
            if (! $eraEditable && $version->isDirty(self::IMMUTABLE_FIELDS)) {
                throw ValidationException::withMessages(['version' => 'Las condiciones de una versión activada o utilizada son inmutables. Cree una nueva versión.']);
            }
        });
        static::deleting(function (self $version): void {
            if (! $version->esEditable()) {
                throw ValidationException::withMessages(['version' => 'No puede eliminarse una versión activada o utilizada.']);
            }
        });
    }
}
