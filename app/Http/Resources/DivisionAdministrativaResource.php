<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DivisionAdministrativaResource extends JsonResource
{
    public function toArray(Request $request)
    {
        if (!$this) {
            return null;
        }

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'nivel' => $this->nivel,
            'tipo' => $this->tipo,
        ];
    }
}
