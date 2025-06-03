<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SicApi extends Model
{
    use HasFactory;

    protected $table = 'sic_apis';
    protected $fillable = ['sic_id', 'nombre', 'clave', 'endpoint_url', 'activo'];

    public function sic()
    {
        return $this->belongsTo(Sic::class);
    }

    public function queries()
    {
        return $this->hasMany(SicQuery::class);
    }
}
