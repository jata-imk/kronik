<?php

namespace App\Models;

use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;

class ProductoVersionComision extends Model
{
    protected $table = 'producto_version_comisiones';

    protected $guarded = [];

    protected $casts = ['importe' => 'decimal:8', 'obligatoria' => 'boolean', 'incluye_cat' => 'boolean', 'fiscalidad' => 'array'];

    protected static function booted(): void
    {
        $guard = function (self $comision): void {
            $ids = array_filter([$comision->producto_version_id, $comision->getRawOriginal('producto_version_id')]);
            foreach (array_unique($ids) as $id) {
                if (! ProductoVersion::findOrFail($id)->esEditable()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['version' => 'Las comisiones de una versión activada o utilizada son inmutables. Cree una nueva versión.']);
                }
            }
        };
        static::saving($guard);
        static::deleting($guard);
    }

    public function concepto()
    {
        return $this->belongsTo(ConceptoComision::class, 'concepto_comision_id');
    }

    public function version()
    {
        return $this->belongsTo(ProductoVersion::class, 'producto_version_id');
    }

    public function calcular(string $monto): string
    {
        return $this->tipo_importe === 'porcentaje'
            ? Decimal::div(Decimal::mul($monto, (string) $this->importe), '100')
            : (string) $this->importe;
    }

    public function esInicial(): bool
    {
        return in_array($this->momento_cobro, ['inicio', 'firma', 'desembolso_descuento'], true);
    }

    public function modalidadInicial(): ?string
    {
        if (! $this->esInicial()) {
            return null;
        }

        return $this->modalidad_cobro ?: match ($this->momento_cobro) {
            'desembolso_descuento' => 'descuento_desembolso',
            default => 'pago_separado',
        };
    }
}
