<?php

namespace App\Services\Communications;

use App\Mail\CommunicationMail;
use App\Models\CommunicationChannel;
use App\Models\CommunicationRule;
use Illuminate\Support\Facades\Mail;

final class EmailTransport implements ChannelTransport
{
    public function capabilities(): array
    {
        return ['templates' => true, 'attachments' => false, 'replies' => false, 'delivery_receipts' => false, 'read_receipts' => false];
    }

    public function send(CommunicationChannel $channel, CommunicationRule $rule, string $recipient, string $subject, string $body, array $variables = [], ?string $correlationId = null, string $format = 'text'): array
    {
        Mail::to($recipient)->send(new CommunicationMail($subject, $body, $channel->sender, $format));

        // SMTP acceptance is not delivery to the mailbox.
        return ['status' => 'accepted', 'message_id' => null];
    }
}
