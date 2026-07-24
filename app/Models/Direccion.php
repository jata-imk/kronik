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

    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }

    public function codigoPostal()
    {
        return $this->belongsTo(CodigoPostal::class, 'codigo_postal_id');
    }

    public function divisionAdministrativaUno()
    {
        return $this->belongsTo(DivisionAdministrativa::class, 'division_admin_uno_id');
    }

    public function divisionAdministrativaDos()
    {
        return $this->belongsTo(DivisionAdministrativa::class, 'division_admin_dos_id');
    }

    public function divisionAdministrativaTres()
    {
        return $this->belongsTo(DivisionAdministrativa::class, 'division_admin_tres_id');
    }

    // Accessor
    public function getCoordenadasAttribute($value)
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            preg_match('/POINT\(([-0-9.]+) ([-0-9.]+)\)/', (string) $value, $matches);

            return [
                'lat' => (float) ($matches[1] ?? 0),
                'lng' => (float) ($matches[2] ?? 0),
            ];
        }

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
            $this->attributes['coordenadas'] = DB::connection()->getDriverName() === 'sqlite'
                ? "POINT({$lat} {$lng})"
                : DB::raw("ST_GeomFromText('POINT($lat $lng)')");
        }
    }
}
