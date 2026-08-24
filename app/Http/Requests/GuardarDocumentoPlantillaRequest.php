<?php

namespace App\Http\Requests;

use App\Enums\DocumentoPlantillaTipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarDocumentoPlantillaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $plantilla = $this->route('plantilla');

        return [
            'clave' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_-]+$/', Rule::unique('documento_plantillas', 'clave')->ignore($plantilla?->id)],
            'nombre' => ['required', 'string', 'max:160'],
            'tipo' => [Rule::requiredIf(! $plantilla), Rule::enum(DocumentoPlantillaTipo::class)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'encabezado_html' => ['nullable', 'string', 'max:50000'],
            'contenido_html' => ['required', 'string', 'max:200000'],
            'pie_html' => ['nullable', 'string', 'max:50000'],
            'resumen_cambios' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'clave.regex' => 'La clave solo puede contener minúsculas, números, guiones y guiones bajos.',
            'clave.unique' => 'Ya existe una plantilla con esta clave.',
            'contenido_html.required' => 'El contenido de la plantilla es obligatorio.',
            'tipo.required' => 'Seleccione el tipo de documento.',
        ];
    }

    public function attributes(): array
    {
        return [
            'clave' => 'clave', 'nombre' => 'nombre', 'tipo' => 'tipo de documento',
            'descripcion' => 'descripción', 'contenido_html' => 'contenido',
            'resumen_cambios' => 'resumen de cambios',
        ];
    }
}
