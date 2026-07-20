<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sic extends Model
{
    use HasFactory;

    protected $table = 'sics';
    protected $fillable = ['nombre', 'clave', 'descripcion', 'activo'];

    public function apis()
    {
        return $this->hasMany(SicApi::class);
    }

    public function queries()
    {
        return $this->hasMany(SicQuery::class);
    }
}
