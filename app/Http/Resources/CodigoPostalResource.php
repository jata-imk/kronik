<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CodigoPostalResource extends JsonResource
{
    public function toArray(Request $request)
    {
        $nivelTres = $this->divisionAdministrativa;
        $nivelDos = $nivelTres?->padre;
        $nivelUno = $nivelDos?->padre;

        return [
            'id' => $this->id,
            'codigo_postal' => $this->codigo,
            'pais' => new PaisResource($this->pais),
            'divisiones_administrativas' => [
                'nivel_uno' => new DivisionAdministrativaResource($nivelUno),
                'nivel_dos' => new DivisionAdministrativaResource($nivelDos),
                'nivel_tres' => new DivisionAdministrativaResource($nivelTres),
            ],
            'datos_adicionales' => $this->datos_adicionales,
        ];
    }
}
