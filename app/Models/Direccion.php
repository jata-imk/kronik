<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Direccion extends Model
{
    protected $table = 'direcciones';
    protected $fillable = [
        'entidad_id',
        'entidad_tipo',
        'tipo',
        'pais_id',
        'codigo_postal_id',
        'linea_uno',
        'linea_dos',
        'linea_tres',
        'division_admin_uno_id',
        'division_admin_dos_id',
        'division_admin_tres_id',
        'datos_adicionales',
        'coordenadas',
    ];

    protected $casts = [
        'datos_adicionales' => 'json',
    ];

    public function entidad()
    {
        return $this->morphTo();
    }

    // Accessor
    public function getCoordenadasAttribute($value)
    {
        // $value es un objeto tipo Point de MySQL
        $point = unpack('x/x/x/x/Corder/Ltype/dlat/dlng', $value);
        return [
            'lat' => $point['lat'],
            'lng' => $point['lng'],
        ];
    }

    // Mutator
    public function setCoordenadasAttribute($value)
    {
        if (is_array($value)) {
            $lat = $value['lat'];
            $lng = $value['lng'];
            $this->attributes['coordenadas'] = DB::raw("ST_GeomFromText('POINT($lng $lat)')");
        }
    }
}
