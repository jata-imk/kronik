<?php

namespace App\Http\Requests\Communications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigureRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage comunicaciones') && $this->user()?->current_team_id !== null;
    }

    public function attributes(): array
    {
        return ['notification_template_id' => 'plantilla global'];
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', Rule::in(['payment_received', 'upcoming_due'])],
            'channel' => ['required', Rule::in(['email', 'sms', 'whatsapp'])],
            'enabled' => ['required', 'boolean'],
            'lead_days' => ['required_if:event_type,upcoming_due', 'nullable', 'integer', 'between:1,'.config('comunicaciones.max_lead_days')],
            'timezone' => ['required', 'timezone'],
            'window_start' => ['required', 'date_format:H:i'],
            'window_end' => ['required', 'date_format:H:i', 'after:window_start'],
            'daily_limit' => ['required', 'integer', 'between:1,'.config('comunicaciones.max_daily_limit')],
            'notification_template_id' => ['required_unless:channel,whatsapp', 'nullable', 'integer', 'exists:notification_templates,id'],
            'subject' => ['nullable', 'string', 'max:160'],
            'body' => ['required_if:channel,whatsapp', 'nullable', 'string', 'max:2000'],
            'external_template_name' => ['required_if:channel,whatsapp', 'nullable', 'string', 'max:120', 'regex:/\A[a-z0-9_]+\z/'],
            'external_template_locale' => ['required_if:channel,whatsapp', 'nullable', 'string', 'max:20'],
            'external_template_status' => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'rejected'])],
        ];
    }
}
