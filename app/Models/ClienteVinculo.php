<?php

namespace App\Models;

use App\Enums\ClienteVinculoRol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteVinculo extends Model
{
    use HasFactory;

    protected $fillable = ['cliente_id', 'cliente_vinculado_id', 'rol', 'notas'];

    protected $casts = ['rol' => ClienteVinculoRol::class];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vinculado()
    {
        return $this->belongsTo(Cliente::class, 'cliente_vinculado_id');
    }
}
