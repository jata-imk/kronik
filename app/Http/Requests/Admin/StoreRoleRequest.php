<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create roles') ?? false;
    }

    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255', Rule::unique('roles', 'name')
            ->where('team_id', $this->user()->current_team_id)
            ->where('guard_name', 'web')->ignore($this->route('role'))];
    }

    public function rules(): array
    {
        return ['name' => $this->nameRules(), 'add_all_permissions' => ['sometimes', 'boolean']];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre del rol', 'permissions' => 'permisos', 'permissions.*' => 'permiso'];
    }
}
