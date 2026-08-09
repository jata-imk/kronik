<?php

namespace App\Http\Requests\Admin;

use App\Rules\Rfc;
use App\Services\CodigoPostalDireccionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmpresaConfiguracionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $domicilio = $this->input('domicilio_fiscal', []);

        $this->merge([
            'rfc' => is_string($this->rfc) ? mb_strtoupper(trim($this->rfc), 'UTF-8') : $this->rfc,
            'pais_base' => is_string($this->pais_base) ? mb_strtoupper(trim($this->pais_base), 'UTF-8') : $this->pais_base,
            'domicilio_fiscal' => is_array($domicilio)
                ? app(CodigoPostalDireccionService::class)->canonicalize($domicilio)
                : $domicilio,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update configuracion-empresa') ?? false;
    }

    public function rules(): array
    {
        $tipoPersonaColumn = $this->input('tipo_persona') === 'fisica' ? 'fisica' : 'moral';

        return [
            'razon_social' => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'tipo_persona' => ['required', 'string', 'in:fisica,moral'],
            'rfc' => ['nullable', new Rfc($this->input('tipo_persona'))],
            'regimen_fiscal_id' => [
                'nullable',
                'integer',
                Rule::exists('regimenes_fiscales', 'id')->where(
                    fn ($query) => $query->where($tipoPersonaColumn, true),
                ),
            ],
            'domicilio_fiscal' => ['nullable', 'array'],
            'domicilio_fiscal.pais_id' => ['nullable', 'integer', 'exists:paises,id'],
            'domicilio_fiscal.pais_codigo_iso' => ['nullable', 'string', 'size:2'],
            'domicilio_fiscal.codigo_postal_id' => [
                'nullable',
                'required_with:domicilio_fiscal.codigo_postal',
                'integer',
                Rule::exists('codigos_postales', 'id')->where(
                    fn ($query) => $query
                        ->where('codigo', $this->input('domicilio_fiscal.codigo_postal'))
                        ->where('division_admin_id', $this->input('domicilio_fiscal.division_admin_tres_id')),
                ),
            ],
            'domicilio_fiscal.division_admin_uno_id' => ['nullable', 'integer', 'exists:divisiones_administrativas,id'],
            'domicilio_fiscal.division_admin_dos_id' => ['nullable', 'integer', 'exists:divisiones_administrativas,id'],
            'domicilio_fiscal.division_admin_tres_id' => ['nullable', 'required_with:domicilio_fiscal.codigo_postal', 'integer', 'exists:divisiones_administrativas,id'],
            'domicilio_fiscal.calle' => ['nullable', 'string', 'max:255'],
            'domicilio_fiscal.numero_exterior' => ['nullable', 'string', 'max:50'],
            'domicilio_fiscal.numero_interior' => ['nullable', 'string', 'max:50'],
            'domicilio_fiscal.colonia' => ['nullable', 'string', 'max:127'],
            'domicilio_fiscal.municipio' => ['nullable', 'string', 'max:127'],
            'domicilio_fiscal.estado' => ['nullable', 'string', 'max:127'],
            'domicilio_fiscal.pais' => ['nullable', 'string', 'max:127'],
            'domicilio_fiscal.codigo_postal' => ['nullable', 'string', 'regex:/^\d{5}$/'],
            'telefono' => ['nullable', 'string', 'max:16', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'email' => ['nullable', 'email', 'max:127'],
            'sitio_web' => ['nullable', 'url', 'max:255'],
            'moneda' => ['required', 'string', 'size:3'],
            'zona_horaria' => ['required', 'timezone'],
            'pais_base' => ['required', 'string', 'size:2', 'exists:paises,codigo_iso'],
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
                    'regimen_fiscal_id',
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

    public function messages(): array
    {
        return [
            'domicilio_fiscal.codigo_postal_id.required_with' => 'Seleccione una colonia válida para el código postal fiscal.',
            'domicilio_fiscal.division_admin_tres_id.required_with' => 'Seleccione una colonia válida para el domicilio fiscal.',
        ];
    }
}
