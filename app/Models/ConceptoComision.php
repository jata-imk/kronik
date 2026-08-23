<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConceptoComision extends Model
{
    protected $table = 'conceptos_comision';

    protected $guarded = [];

    protected $casts = ['es_oficial_reco' => 'boolean', 'revisado' => 'boolean', 'activo' => 'boolean', 'vigente_desde' => 'date', 'retirado_desde' => 'date'];
}
