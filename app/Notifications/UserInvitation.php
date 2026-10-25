<?php

namespace App\Notifications;

use App\Services\Mail\AccountMailFactory;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification
{
    public function __construct(public string $token) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return app(AccountMailFactory::class)->invitation($notifiable, $this->token);
    }
}
