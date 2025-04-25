<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DireccionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $nivelTres = $this->divisionAdministrativaTres;
        $nivelDos = $this->divisionAdministrativaDos;
        $nivelUno = $this->divisionAdministrativaUno;

        return [
            'tipo' => $this->tipo,
            'pais' => new PaisResource($this->pais),
            'codigo_postal' => new CodigoPostalResource($this->codigoPostal),
            'linea_uno' => $this->linea_uno,
            'linea_dos' => $this->linea_dos,
            'linea_tres' => $this->linea_tres,
            'divisiones_administrativas' => [
                'nivel_uno' => new DivisionAdministrativaResource($nivelUno),
                'nivel_dos' => new DivisionAdministrativaResource($nivelDos),
                'nivel_tres' => new DivisionAdministrativaResource($nivelTres)
            ],
            'datos_adicionales' => $this->datos_adicionales,
            'coordenadas' => $this->coordenadas
        ];
    }
}
