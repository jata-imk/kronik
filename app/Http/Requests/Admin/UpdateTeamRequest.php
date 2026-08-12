<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update teams') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('teams', 'name')->ignore($this->route('team'))],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre del equipo'];
    }
}
