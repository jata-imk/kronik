<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEmpresaConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update configuracion-empresa') ?? false;
    }

    public function rules(): array
    {
        return [
            'razon_social' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/'],
            'regimen_fiscal' => ['nullable', 'string', 'max:255'],
            'domicilio_fiscal' => ['nullable', 'array'],
            'domicilio_fiscal.calle' => ['nullable', 'string', 'max:255'],
            'domicilio_fiscal.numero_exterior' => ['nullable', 'string', 'max:50'],
            'domicilio_fiscal.numero_interior' => ['nullable', 'string', 'max:50'],
            'domicilio_fiscal.colonia' => ['nullable', 'string', 'max:127'],
            'domicilio_fiscal.municipio' => ['nullable', 'string', 'max:127'],
            'domicilio_fiscal.estado' => ['nullable', 'string', 'max:127'],
            'domicilio_fiscal.codigo_postal' => ['nullable', 'string', 'max:15'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:127'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
            'moneda' => ['required', 'string', 'size:3'],
            'zona_horaria' => ['required', 'timezone'],
            'pais_base' => ['required', 'string', 'size:2'],
            'logotipo_path' => ['nullable', 'string', 'max:255'],
            'parametros_operativos' => ['nullable', 'array'],
            'parametros_operativos.dias_gracia_default' => ['nullable', 'integer', 'min:0', 'max:365'],
            'parametros_operativos.hora_corte_operativo' => ['nullable', 'string', 'max:8'],
            'parametros_operativos.dias_inhabiles' => ['nullable', 'array'],
            'parametros_operativos.reglas_cobranza' => ['nullable', 'array'],
            'parametros_operativos.formatos_contrato' => ['nullable', 'array'],
            'parametros_operativos.cuentas_bancarias' => ['nullable', 'array'],
            'parametros_operativos.contactos' => ['nullable', 'array'],
            'integraciones' => ['nullable', 'array'],
            'integraciones.circulo_credito_host' => ['nullable', 'url', 'max:255'],
            'integraciones.circulo_credito_sandbox' => ['nullable', 'boolean'],
            'integraciones.circulo_credito_api_key' => ['nullable', 'string', 'max:255'],
            'estatus' => ['required', 'string', 'in:borrador,activa,suspendida'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('estatus') !== 'activa') {
                    return;
                }

                foreach ([
                    'razon_social',
                    'rfc',
                    'regimen_fiscal',
                    'email',
                    'domicilio_fiscal.calle',
                    'domicilio_fiscal.codigo_postal',
                    'domicilio_fiscal.estado',
                ] as $field) {
                    if (blank(data_get($this->all(), $field))) {
                        $validator->errors()->add($field, 'Este campo es obligatorio para activar la financiera.');
                    }
                }
            },
        ];
    }
}
