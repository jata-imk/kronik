<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoCrediticio extends Model
{
    protected $table = 'productos_crediticios';

    protected $guarded = [];

    protected $casts = ['activo' => 'boolean'];

    public function versiones(): HasMany
    {
        return $this->hasMany(ProductoVersion::class);
    }
}
