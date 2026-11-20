<?php

namespace App\Services\Communications;

use App\Models\CommunicationChannel;
use App\Models\CommunicationRule;

interface ChannelTransport
{
    /** @return array{templates: bool, attachments: bool, replies: bool, delivery_receipts: bool, read_receipts: bool} */
    public function capabilities(): array;

    /** @return array{status: string, message_id: ?string} */
    public function send(CommunicationChannel $channel, CommunicationRule $rule, string $recipient, string $subject, string $body, array $variables = []): array;
}
