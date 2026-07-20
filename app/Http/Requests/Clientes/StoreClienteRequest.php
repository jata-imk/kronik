<?php

namespace App\Http\Requests\Clientes;

use App\Http\Requests\Traits\ExtendRulesTrait;

class StoreClienteRequest extends BaseClienteRequest
{
    use ExtendRulesTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Cliente::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $baseRules = $this->baseRules();

        $additionalRules = [
            'primer_nombre' => ['required'],
            'apellido_paterno' => ['required'],
            'fecha_nacimiento' => ['required'],
            'pais_nacimiento_id' => ['required'],
            'telefono' => ['required'],
            'email' => ['required'],
            'sexo' => ['required'],
            'datos_fiscales' => ['required'],
            'datos_fiscales.tipo_persona' => ['required'],
            'datos_fiscales.curp' => ['required'],
            'datos_fiscales.rfc' => ['required'],
            'direcciones' => ['required', 'min:1'],
            'direcciones.*.linea_uno' => ['required'],
            'direcciones.*.division_admin_uno_id' => ['required'],
            'direcciones.*.division_admin_dos_id' => ['required'],
            'direcciones.*.division_admin_tres_id' => ['required'],
        ];

        return $this->extendRules($baseRules, $additionalRules);
    }
}
