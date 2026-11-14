<?php

namespace App\Http\Requests;

use App\Services\OriginacionPoliticaService;
use Illuminate\Foundation\Http\FormRequest;

class GuardarOriginacionPoliticaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('read productos-crediticios')
            && $this->user()->can('manage origination productos-crediticios');
    }

    public function rules(): array
    {
        return OriginacionPoliticaService::reglas();
    }
}
