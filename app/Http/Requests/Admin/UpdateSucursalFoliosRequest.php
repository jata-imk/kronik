<?php

namespace App\Http\Requests\Admin;

use App\Enums\TipoFolio;
use App\Services\FolioService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSucursalFoliosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update sucursales');
    }

    public function rules(): array
    {
        $rules = ['prefijo_folio' => ['required', 'string', 'max:20'], 'version' => ['required', 'integer', 'min:0'],
            'siguientes' => ['required', 'array:solicitud,contrato,credito,recibo']];
        foreach (TipoFolio::cases() as $tipo) {
            $rules['siguientes.'.$tipo->value] = ['required', 'integer', 'min:1', 'max:'.FolioService::MAXIMO];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return ['prefijo_folio' => 'prefijo de folios', 'version' => 'versión de la numeración', 'siguientes' => 'siguientes números',
            'siguientes.solicitud' => 'siguiente solicitud', 'siguientes.contrato' => 'siguiente contrato',
            'siguientes.credito' => 'siguiente crédito', 'siguientes.recibo' => 'siguiente recibo'];
    }
}
