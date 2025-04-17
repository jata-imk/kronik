<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return  [
            'id' => $this->id,
            'nombre_es' => $this->nombre_es,
            'nombre_us' => $this->nombre_us,
            'nombre_nativo' => $this->nombre_nativo,
            'idiomas' => $this->idiomas,
            'codigo_iso' => $this->codigo_iso,
            'codigo_iso3' => $this->codigo_iso3,
            'emoji' => $this->emoji,
            'mapas' => $this->mapas,
            'formato_direccion' => $this->formato_direccion,
        ];
    }
}
