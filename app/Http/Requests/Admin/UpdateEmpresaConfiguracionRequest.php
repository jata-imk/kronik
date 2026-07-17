<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEmpresaConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update empresa-configuracion') ?? false;
    }

    public function rules(): array
    {
        return [
            'razon_social' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13', 'regex:/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/'],
            'regimen_fiscal_id' => ['nullable', 'exists:regimenes_fiscales,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'domicilio_fiscal' => ['nullable', 'array'],
            'domicilio_fiscal.calle' => ['nullable', 'string', 'max:255'],
            'domicilio_fiscal.numero_exterior' => ['nullable', 'string', 'max:50'],
            'domicilio_fiscal.numero_interior' => ['nullable', 'string', 'max:50'],
            'domicilio_fiscal.colonia' => ['nullable', 'string', 'max:255'],
            'domicilio_fiscal.municipio' => ['nullable', 'string', 'max:255'],
            'domicilio_fiscal.estado' => ['nullable', 'string', 'max:255'],
            'domicilio_fiscal.codigo_postal' => ['nullable', 'string', 'max:10'],
            'domicilio_fiscal.pais' => ['nullable', 'string', 'max:100'],
            'moneda' => ['required', 'string', 'size:3'],
            'zona_horaria' => ['required', 'timezone'],
            'horario_operacion' => ['nullable', 'array'],
            'folio_credito_prefijo' => ['nullable', 'string', 'max:20'],
            'folio_credito_siguiente' => ['required', 'integer', 'min:1'],
            'dias_inhabiles' => ['nullable', 'array'],
            'reglas_cobranza' => ['nullable', 'array'],
            'formatos_contrato' => ['nullable', 'array'],
            'cuentas_bancarias' => ['nullable', 'array'],
            'contactos' => ['nullable', 'array'],
            'integraciones' => ['nullable', 'array'],
            'integraciones.circulo_credito' => ['nullable', 'array'],
            'integraciones.circulo_credito.habilitado' => ['nullable', 'boolean'],
            'integraciones.circulo_credito.env_prefix' => ['nullable', 'string', 'max:80', 'regex:/^[A-Z0-9_]+$/'],
            'integraciones.geocoding' => ['nullable', 'array'],
            'integraciones.geocoding.habilitado' => ['nullable', 'boolean'],
            'integraciones.geocoding.env_key' => ['nullable', 'string', 'max:80', 'regex:/^[A-Z0-9_]+$/'],
            'activa' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->boolean('activa')) {
                    return;
                }

                foreach ([
                    'razon_social',
                    'rfc',
                    'regimen_fiscal_id',
                    'email',
                    'domicilio_fiscal.calle',
                    'domicilio_fiscal.codigo_postal',
                    'domicilio_fiscal.estado',
                    'domicilio_fiscal.pais',
                ] as $field) {
                    if (blank(data_get($this->all(), $field))) {
                        $validator->errors()->add($field, 'Este campo es obligatorio para activar la financiera.');
                    }
                }
            },
        ];
    }
}
