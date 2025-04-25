<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteDatosFiscalesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_cliente' => $this->id_cliente,
            'tipo_persona' => $this->tipo_persona,
            'regimen_fiscal' => new RegimenFiscalResource($this->regimenFiscal),
            'curp' => $this->curp,
            'rfc' => $this->rfc,
            'razon_social' => $this->razon_social
        ];
    }
}
