<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenubarItemRequest extends FormRequest
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
        return [
            'modules' => 'present|array',
            'modules.*' => 'array:routes',
            'modules.*.routes' => 'present|array',
            'modules.*.routes.*' => 'string|max:255|distinct',
            'label' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'type' => [
                'required',
                Rule::in(['menu', 'route:name', 'route:static', 'route:dynamic', 'route:referer_fallback']),
            ],
            'value' => 'nullable|string',
            'params' => 'nullable|string',
            'parent_id' => 'nullable|exists:menubar_items,id',
            'sort_order' => 'nullable|integer|min:0|max:2147483647',
        ];
    }

    public function attributes(): array
    {
        return ['modules' => 'pantallas de aparición', 'modules.*.routes' => 'pantallas seleccionadas',
            'modules.*.routes.*' => 'pantalla seleccionada', 'label' => 'etiqueta', 'icon' => 'icono',
            'type' => 'tipo de elemento', 'value' => 'destino', 'params' => 'parámetros', 'parent_id' => 'grupo', 'sort_order' => 'orden'];
    }
}
