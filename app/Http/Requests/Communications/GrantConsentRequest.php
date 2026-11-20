<?php

namespace App\Http\Requests\Communications;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GrantConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('cliente');

        return $client instanceof Cliente && $this->user()?->can('manage comunicaciones')
            && $this->user()?->can('update', $client) && $this->user()?->current_team_id !== null;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::in(['email', 'sms', 'whatsapp'])],
            'purpose' => ['required', Rule::in(['operational', 'preventive'])],
            'source' => ['required', 'string', 'max:80'],
            'evidence_reference' => ['required', 'string', 'max:255'],
        ];
    }
}
