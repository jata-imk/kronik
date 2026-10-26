<?php

namespace App\Console\Commands;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Mail\AccountMailFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class PreviewAccountMail extends Command
{
    protected $signature = 'mail:preview';

    protected $description = 'Genera vistas previas ficticias de correos de acceso, sin enviar mensajes (solo local/testing)';

    public function handle(AccountMailFactory $factory): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Las vistas previas solo se pueden generar en local o testing.');

            return self::FAILURE;
        }

        if (config('filesystems.disks.local.driver') !== 'local') {
            $this->error('Las vistas previas requieren que el disco local use almacenamiento local.');

            return self::FAILURE;
        }

        $user = new User(['name' => 'Andrea Martínez', 'email' => 'andrea@example.test', 'status' => UserStatus::Pending]);
        $invitation = $factory->invitation($user, 'SOLO-VISTA-PREVIA');
        $user->status = UserStatus::Active;
        $recovery = $factory->passwordReset($user, 'SOLO-VISTA-PREVIA');

        // Las muestras nunca contienen tokens utilizables ni firmas válidas.
        $previewUrl = 'https://preview.example.invalid/reset-password/SOLO-VISTA-PREVIA?email=andrea%40example.test';
        $invitation->viewData['actionUrl'] = $previewUrl;
        $recovery->viewData['actionUrl'] = $previewUrl;
        $verification = $factory->verification($user, 'https://preview.example.invalid/email/verify/0/SOLO-VISTA-PREVIA');

        $disk = Storage::disk('local');
        foreach (['invitacion' => $invitation, 'recuperacion' => $recovery, 'verificacion' => $verification] as $name => $message) {
            $html = view($message->view['html'], $message->data())->render();
            $text = view($message->view['text'], $message->data())->render();
            $email = (new Email)
                ->from(new Address(config('mail.from.address'), config('mail.from.name')))
                ->to('andrea@example.test')
                ->subject($message->subject)
                ->html($html)
                ->text($text);

            foreach (['html' => $html, 'txt' => $text, 'eml' => $email->toString()] as $extension => $contents) {
                $path = "mail-previews/{$name}.{$extension}";
                if (! $disk->put($path, $contents)) {
                    $this->error("No se pudo escribir la vista previa: {$path}");

                    return self::FAILURE;
                }
            }
        }

        $this->info('Vistas previas HTML, TXT y EML: '.$disk->path('mail-previews'));
        $this->comment('Datos ficticios y enlaces no utilizables. No se enviaron correos ni se modificaron usuarios.');

        return self::SUCCESS;
    }
}
