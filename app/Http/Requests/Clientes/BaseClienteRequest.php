<?php

namespace App\Http\Requests\Clientes;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseClienteRequest extends FormRequest
{
    protected function baseRules(): array
    {
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
            'datos_fiscales.regimen_fiscal_id' => ['nullable', 'integer', 'exists:regimenes_fiscales,id'],
            'datos_fiscales.curp' => ['string', 'max:255'],
            'datos_fiscales.rfc' => ['string', 'max:255'],
            'datos_fiscales.razon_social' => ['nullable', 'string', 'max:255'],

            // Direcciones
            'direcciones' => ['array'],
            'direcciones.*.entidad_id' => ['integer'],
            'direcciones.*.entidad_tipo' => ['string', 'in:clientes'], // puedes ajustar según tus políticas
            'direcciones.*.tipo' => ['nullable', 'string', 'max:10'],
            'direcciones.*.pais_id' => ['integer', 'exists:paises,id'],
            'direcciones.*.codigo_postal' => ['nullable', 'string', 'max:15'],
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
}
