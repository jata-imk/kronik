<?php

namespace App\Http\Requests\Communications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('template') ? 'update plantillas-notificaciones' : 'create plantillas-notificaciones') ?? false;
    }

    public function rules(): array
    {
        return [
            'key' => [$this->route('template') ? 'sometimes' : 'required', 'string', 'max:80', 'regex:/^[a-z0-9_-]+$/', Rule::unique('notification_templates', 'key')->ignore($this->route('template')?->id)],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'channel' => [$this->route('template') ? 'sometimes' : 'required', Rule::in(['sms', 'email'])],
            'event_type' => [$this->route('template') ? 'sometimes' : 'required', Rule::in(['payment_received', 'upcoming_due'])],
            'format' => ['required', Rule::in(['text', 'html'])],
            'subject' => ['nullable', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:200000'],
            'change_summary' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['key' => 'clave', 'name' => 'nombre', 'description' => 'descripción', 'channel' => 'canal',
            'event_type' => 'evento', 'format' => 'formato', 'subject' => 'asunto', 'body' => 'contenido', 'change_summary' => 'resumen de cambios'];
    }

    public function messages(): array
    {
        return ['key.unique' => 'Ya existe una plantilla con esta clave.', 'key.regex' => 'Use minúsculas, números, guiones o guiones bajos en la clave.'];
    }
}
