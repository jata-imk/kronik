<?php

namespace App\Models;

use App\Enums\ClienteDocumentoEstado;
use App\Enums\ClienteDocumentoTipo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteDocumento extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'reemplaza_documento_id',
        'tipo',
        'nombre',
        'estado',
        'version',
        'es_actual',
        'disk',
        'path',
        'nombre_original',
        'mime_type',
        'tamano_bytes',
        'recibido_en',
        'revisado_en',
        'vence_en',
        'revisado_por',
        'motivo_rechazo',
        'notas',
    ];

    protected $casts = [
        'tipo' => ClienteDocumentoTipo::class,
        'estado' => ClienteDocumentoEstado::class,
        'es_actual' => 'boolean',
        'recibido_en' => 'datetime',
        'revisado_en' => 'datetime',
        'vence_en' => 'date',
    ];

    protected $hidden = ['disk', 'path'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function reemplaza()
    {
        return $this->belongsTo(self::class, 'reemplaza_documento_id');
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}
