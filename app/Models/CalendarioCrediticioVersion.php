<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CalendarioCrediticioVersion extends Model
{
    protected $table = 'calendario_crediticio_versiones';

    protected $fillable = ['configuracion', 'revision', 'publicada_en', 'orden_publicacion', 'huella'];

    protected $casts = ['configuracion' => 'array', 'publicada_en' => 'immutable_datetime', 'revision' => 'integer'];
}
