<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateRoleRequest extends StoreRoleRequest
{
    public function authorize(): bool
    {
        return app(\App\Services\TeamContext::class)->allows($this->user(), app(\App\Services\TeamContext::class)->resolve($this), 'update roles');
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
