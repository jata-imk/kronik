<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoVersionPeriodicidad extends Model
{
    protected $table = 'producto_version_periodicidades';

    protected $guarded = [];

    public function version()
    {
        return $this->belongsTo(ProductoVersion::class, 'producto_version_id');
    }
}
