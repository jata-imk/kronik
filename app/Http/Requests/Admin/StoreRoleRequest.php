<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(\App\Services\TeamContext::class)->allows($this->user(), app(\App\Services\TeamContext::class)->resolve($this), 'create roles');
    }

    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255', Rule::unique('roles', 'name')
            ->where('team_id', app(\App\Services\TeamContext::class)->resolve($this)->id)
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
