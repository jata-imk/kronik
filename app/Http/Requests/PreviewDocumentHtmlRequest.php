<?php

namespace App\Http\Requests;

use App\Enums\DocumentoPlantillaTipo;
use App\Models\DocumentoPlantillaVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewDocumentHtmlRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()?->can('read plantillas-documentos')) {
            return false;
        }
        if ($this->filled('version_id')) {
            $version = DocumentoPlantillaVersion::findOrFail($this->integer('version_id'));

            return $this->user()->can('update plantillas-documentos') && $version->esEditable()
                && $this->input('tipo') === $version->plantilla->tipo->value;
        }

        return $this->user()->can('create plantillas-documentos');
    }

    public function rules(): array
    {
        return [
            'version_id' => 'nullable|integer|min:1',
            'tipo' => ['required', Rule::enum(DocumentoPlantillaTipo::class)],
            'contenido_html' => 'required|string|max:200000', 'encabezado_html' => 'nullable|string|max:50000',
            'pie_html' => 'nullable|string|max:50000', 'presentacion' => 'nullable|array:marca_agua,formato',
            'presentacion.marca_agua' => 'nullable|string|max:80',
        ];
    }

    public function attributes(): array
    {
        return ['contenido_html' => 'contenido', 'encabezado_html' => 'encabezado', 'pie_html' => 'pie de página', 'tipo' => 'tipo de documento'];
    }
}
