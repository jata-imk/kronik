<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateRoleRequest extends StoreRoleRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update roles') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ];
    }
}
