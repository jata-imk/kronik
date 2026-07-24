<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'primer_nombre' => $this->primer_nombre,
            'segundo_nombre' => $this->segundo_nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'nombre_completo' => implode(' ', array_filter([
                $this->primer_nombre,
                $this->segundo_nombre,
                $this->apellido_paterno,
                $this->apellido_materno,
            ])),
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'pais_nacimiento' => new PaisResource($this->paisNacimiento),
            'telefono_codigo_pais' => $this->telefono_codigo_pais,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'sexo' => $this->sexo,
            'ocupacion' => $this->ocupacion,
            'actividad_economica' => $this->actividad_economica,
            'ingresos_mensuales' => $this->ingresos_mensuales,
            'egresos_mensuales' => $this->egresos_mensuales,
            'origen_recursos' => $this->origen_recursos,
            'datos_fiscales' => new ClienteDatosFiscalesResource($this->datosFiscales),
            'direcciones' => DireccionResource::collection($this->direcciones),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
