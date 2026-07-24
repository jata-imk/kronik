<?php

namespace App\Models;

use App\Enums\ClienteReferenciaTipo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteReferencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'tipo',
        'nombre',
        'relacion',
        'empresa',
        'puesto',
        'telefono_codigo_pais',
        'telefono',
        'email',
        'notas',
    ];

    protected $casts = ['tipo' => ClienteReferenciaTipo::class];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
