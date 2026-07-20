<?php

namespace App\Models;

use App\Enums\ConsentimientoSicMedio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteConsentimientoSic extends Model
{
    use HasFactory;

    protected $table = 'cliente_consentimientos_sic';

    protected $fillable = [
        'cliente_id',
        'registrado_por',
        'medio',
        'otorgado_en',
        'vence_en',
        'revocado_en',
        'evidencia_disk',
        'evidencia_path',
        'evidencia_nombre_original',
        'evidencia_mime_type',
        'evidencia_tamano_bytes',
        'notas',
    ];

    protected $casts = [
        'medio' => ConsentimientoSicMedio::class,
        'otorgado_en' => 'datetime',
        'vence_en' => 'date',
        'revocado_en' => 'datetime',
    ];

    protected $hidden = ['evidencia_disk', 'evidencia_path'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
