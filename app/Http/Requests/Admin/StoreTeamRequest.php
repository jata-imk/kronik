<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create teams') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255', 'unique:teams,name']];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre del equipo'];
    }
}
