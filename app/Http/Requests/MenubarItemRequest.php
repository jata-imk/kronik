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
            'modules' => 'required|array',
            'label' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'type' => [
                'required',
                Rule::in(['menu', 'route:name', 'route:static', 'route:dynamic', 'route:referer_fallback']),
            ],
            'value' => 'nullable|string',
            'params' => 'nullable|string',
            'parent_id' => 'nullable|exists:menubar_items,id',
            'sort_order' => 'nullable|integer',
        ];
    }
}
