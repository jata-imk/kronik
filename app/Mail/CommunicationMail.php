<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommunicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $messageSubject, public string $messageBody, public string $sender) {}

    public function build(): self
    {
        return $this->from($this->sender)->subject($this->messageSubject)->text('emails.communication')
            ->with(['messageBody' => $this->messageBody]);
    }
}
