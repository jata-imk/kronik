<?php

namespace App\Services\Communications;

final class LabsMobileConfiguration
{
    public function configured(): bool
    {
        $sender = config('comunicaciones.sms.sender');

        return $this->credentialsConfigured() && is_string($sender) && filled($sender)
            && preg_match('/\A(?:[0-9]{1,16}|[a-zA-Z0-9 ]{1,11})\z/', $sender) === 1;
    }

    public function credentialsConfigured(): bool
    {
        return is_string(config('comunicaciones.sms.username')) && filled(config('comunicaciones.sms.username'))
            && is_string(config('comunicaciones.sms.token')) && filled(config('comunicaciones.sms.token'))
            && config('comunicaciones.sms.base_url') === 'https://api.labsmobile.com/json';
    }

    public function sendingEnabled(): bool
    {
        return config('comunicaciones.send_enabled') === true && config('comunicaciones.sms_send_enabled') === true;
    }

    /** Only presence indicators may leave the server. */
    public function presence(): array
    {
        return ['configured' => $this->configured(), 'credentials' => $this->credentialsConfigured(),
            'sender' => filled(config('comunicaciones.sms.sender')), 'sending_enabled' => $this->sendingEnabled()];
    }
}
