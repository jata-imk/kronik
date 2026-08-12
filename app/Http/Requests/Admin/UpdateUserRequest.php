<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update users') ?? false;
    }

    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target)],
            'current_team_id' => ['required', 'integer', 'exists:teams,id'],
            'team_roles' => ['required', 'array', 'min:1'],
            'team_roles.*.team_id' => ['required', 'integer', 'distinct', 'exists:teams,id'],
            'team_roles.*.role_ids' => ['present', 'array'],
            'team_roles.*.role_ids.*' => ['integer', 'distinct', 'exists:roles,id'],
            'sucursal_ids' => ['required', 'array', 'min:1'],
            'sucursal_ids.*' => ['integer', 'distinct', 'exists:sucursales,id'],
            'sucursal_principal_id' => ['required', 'integer', 'exists:sucursales,id'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'is_super_admin' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'current_team_id' => 'equipo actual',
            'team_roles' => 'equipos y roles',
            'sucursal_ids' => 'sucursales',
            'sucursal_principal_id' => 'sucursal principal',
            'status' => 'estado',
        ];
    }
}
