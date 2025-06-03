<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SicQuery extends Model
{
    use HasFactory;

    protected $table = 'sic_queries';
    protected $fillable = [
        'cliente_id',
        'sic_id',
        'sic_api_id',
        'fecha_consulta',
        'status',
        'mensaje_error',
        'response_data',
    ];

    protected $casts = [
        'response_data' => 'array',
        'fecha_consulta' => 'datetime',
    ];

    public function sic()
    {
        return $this->belongsTo(Sic::class);
    }

    public function api()
    {
        return $this->belongsTo(SicApi::class, 'sic_api_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function results()
    {
        return $this->hasMany(SicQueryResult::class);
    }
}
