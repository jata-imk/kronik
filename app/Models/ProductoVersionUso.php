<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoVersionUso extends Model
{
    protected $table = 'producto_version_usos';

    protected $guarded = [];

    protected $casts = ['snapshot' => 'array'];
}
