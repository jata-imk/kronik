<?php

namespace App\Http\Requests\Clientes;

use App\Models\CodigoPostal;
use App\Rules\Rfc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class BaseClienteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $datosFiscales = $this->input('datos_fiscales', []);

        if (is_array($datosFiscales) && is_string($datosFiscales['rfc'] ?? null)) {
            $datosFiscales['rfc'] = mb_strtoupper(trim($datosFiscales['rfc']), 'UTF-8');
            $this->merge(['datos_fiscales' => $datosFiscales]);
        }
    }

    protected function baseRules(): array
    {
        $tipoPersonaColumn = $this->input('datos_fiscales.tipo_persona') === 'fisica' ? 'fisica' : 'moral';

        return [
            // Datos del cliente
            'primer_nombre' => ['string', 'max:127'],
            'segundo_nombre' => ['nullable', 'string', 'max:127'],
            'apellido_paterno' => ['string', 'max:127'],
            'apellido_materno' => ['nullable', 'string', 'max:127'],
            'fecha_nacimiento' => ['date'],
            'pais_nacimiento_id' => ['integer', 'exists:paises,id'],
            'telefono_codigo_pais' => ['nullable', 'string', 'max:4'],
            'telefono' => ['string', 'max:15'],
            'email' => ['email', 'max:127'],
            'sexo' => ['in:masculino,femenino'],
            'ocupacion' => ['nullable', 'string', 'max:127'],
            'actividad_economica' => ['nullable', 'string', 'max:255'],
            'ingresos_mensuales' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'egresos_mensuales' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'origen_recursos' => ['nullable', 'string', 'max:2000'],

            // Datos fiscales
            'datos_fiscales' => ['array'],
            'datos_fiscales.tipo_persona' => ['in:fisica,moral'],
            'datos_fiscales.regimen_fiscal_id' => [
                'nullable',
                'integer',
                Rule::exists('regimenes_fiscales', 'id')->where(
                    fn ($query) => $query->where($tipoPersonaColumn, true),
                ),
            ],
            'datos_fiscales.curp' => ['string', 'max:255'],
            'datos_fiscales.rfc' => [new Rfc($this->input('datos_fiscales.tipo_persona'))],
            'datos_fiscales.razon_social' => ['nullable', 'string', 'max:255'],

            // Direcciones
            'direcciones' => ['array'],
            'direcciones.*.entidad_id' => ['integer'],
            'direcciones.*.entidad_tipo' => ['string', 'in:clientes'], // puedes ajustar según tus políticas
            'direcciones.*.tipo' => ['nullable', 'string', 'max:10'],
            'direcciones.*.pais_id' => ['nullable', 'integer', 'exists:paises,id'],
            'direcciones.*.codigo_postal' => ['nullable', 'string', 'regex:/^\d{5}$/'],
            'direcciones.*.codigo_postal_id' => ['nullable', 'integer', 'exists:codigos_postales,id'],
            'direcciones.*.linea_uno' => ['string', 'max:255'],
            'direcciones.*.linea_dos' => ['nullable', 'string', 'max:127'],
            'direcciones.*.linea_tres' => ['nullable', 'string', 'max:127'],
            'direcciones.*.division_admin_uno_id' => ['integer'],
            'direcciones.*.division_admin_dos_id' => ['integer'],
            'direcciones.*.division_admin_tres_id' => ['nullable', 'integer'],
            'direcciones.*.datos_adicionales' => ['nullable', 'array'],
            'direcciones.*.coordenadas.lat' => ['numeric', 'between:-90,90'],
            'direcciones.*.coordenadas.lng' => ['numeric', 'between:-180,180'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ((array) $this->input('direcciones', []) as $index => $direccion) {
                    if (! is_array($direccion)) {
                        continue;
                    }

                    $codigo = $direccion['codigo_postal'] ?? null;

                    if (blank($codigo)) {
                        continue;
                    }

                    $codigoPostalId = $direccion['codigo_postal_id'] ?? null;
                    $divisionId = $direccion['division_admin_tres_id'] ?? null;

                    if (blank($codigoPostalId)) {
                        $validator->errors()->add(
                            "direcciones.$index.codigo_postal_id",
                            'Seleccione el código postal de la lista.',
                        );
                    }

                    if (blank($divisionId)) {
                        $validator->errors()->add(
                            "direcciones.$index.division_admin_tres_id",
                            'Seleccione una colonia.',
                        );
                    }

                    if (
                        blank($codigoPostalId)
                        || blank($divisionId)
                        || ! is_numeric($codigoPostalId)
                        || ! is_numeric($divisionId)
                    ) {
                        continue;
                    }

                    $coincide = CodigoPostal::query()
                        ->whereKey($codigoPostalId)
                        ->where('codigo', $codigo)
                        ->where('division_admin_id', $divisionId)
                        ->exists();

                    if (! $coincide) {
                        $validator->errors()->add(
                            "direcciones.$index.codigo_postal_id",
                            'El código postal no corresponde con la colonia seleccionada.',
                        );
                    }
                }
            },
        ];
    }
}
