<?php

namespace App\Http\Requests\Communications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigureChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage comunicaciones') && $this->user()?->current_team_id !== null;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::in(['email', 'sms', 'whatsapp'])],
            'sender' => ['required', 'string', 'max:255'],
            'secret' => ['nullable', 'string', 'max:2000'],
            'enabled' => ['required', 'boolean'],
            'eligibility' => ['required', Rule::in(['unverified', 'testing', 'approved', 'rejected'])],
            'eligibility_reference' => ['required_if:eligibility,approved', 'nullable', 'string', 'max:255'],
        ];
    }
}
