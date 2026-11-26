<?php

function notificationTemplateQa(array $data = [], string $state = 'active'): App\Models\NotificationTemplate
{
    $data = array_replace(['key' => 'qa-'.str()->uuid(), 'name' => 'Plantilla QA', 'event_type' => 'payment_received',
        'channel' => 'sms', 'format' => 'text', 'body' => 'Hola {nombre}, registramos tu pago.', 'subject' => 'Pago recibido'], $data);
    $template = App\Models\NotificationTemplate::create(Illuminate\Support\Arr::only($data, ['key', 'name', 'event_type', 'channel']));
    $template->versions()->create([...app(App\Services\Communications\NotificationContent::class)->content($data['channel'], $data),
        'state' => $state, 'number' => 1]);

    return $template;
}

function communicationRuleQa(array $data): App\Models\CommunicationRule
{
    if ($data['channel'] !== 'whatsapp') {
        $data['notification_template_id'] = notificationTemplateQa($data)->id;
    }

    return App\Models\CommunicationRule::create($data);
}
