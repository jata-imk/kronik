<?php

namespace App\Http\Requests\Communications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendWhatsAppTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('send test comunicaciones') && $this->user()?->current_team_id !== null;
    }

    public function rules(): array
    {
        return [
            'recipient' => ['required', 'string', 'regex:/\A\+[1-9][0-9]{7,14}\z/'],
            'event_type' => ['required', Rule::in(['payment_received', 'upcoming_due'])],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient.required' => 'Indica el número autorizado para la prueba.',
            'recipient.string' => 'Indica un número de prueba válido.',
            'recipient.regex' => 'Indica el número en formato internacional, por ejemplo +525512345678.',
            'event_type.required' => 'Selecciona el evento de la plantilla de prueba.',
            'event_type.in' => 'Selecciona un evento válido para la prueba.',
        ];
    }

    public function attributes(): array
    {
        return [
            'recipient' => 'destinatario de prueba',
            'event_type' => 'evento de la plantilla',
        ];
    }
}
