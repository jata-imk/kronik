<?php

namespace App\Http\Requests\Clientes;

use App\Http\Requests\Clientes\BaseClienteRequest;

class UpdateClienteRequest extends BaseClienteRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return collect($this->baseRules())
            ->mapWithKeys(fn($rules, $key) => [$key => array_merge(['sometimes'], $rules)])
            ->toArray();
    }
}
