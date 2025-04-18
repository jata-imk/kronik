<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegimenFiscalResource extends JsonResource
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
            'clave' => $this->clave,
            'descripcion' => $this->descripcion,
            "fisica" => $this->fisica,
            "moral" => $this->moral,
            "fecha_inicio_vigencia" => $this->fecha_inicio_vigencia,
            "fecha_fin_vigencia" => $this->fecha_fin_vigencia,
        ];
    }
}
