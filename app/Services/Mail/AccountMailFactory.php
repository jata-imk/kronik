<?php

namespace App\Services\Mail;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class AccountMailFactory
{
    public function invitation(User $user, string $token): MailMessage
    {
        return $this->passwordReset($user, $token, true);
    }

    public function passwordReset(User $user, string $token, bool $invitationFlow = false): MailMessage
    {
        $invitation = $user->status === UserStatus::Pending;
        $appName = config('app.name');
        $broker = $invitationFlow ? 'invitaciones' : 'users';
        $minutes = (int) config("auth.passwords.{$broker}.expire");
        $expiry = $invitationFlow && $minutes >= 60 && $minutes % 60 === 0 ? ($minutes / 60).' horas' : "{$minutes} minutos";

        return $this->message($user, [
            'subject' => $invitation ? "Te invitaron a {$appName}" : "Restablece tu contraseña en {$appName}",
            'eyebrow' => $invitation ? 'TU ESPACIO DE TRABAJO' : 'SEGURIDAD DE TU CUENTA',
            'title' => $invitation ? 'Tu equipo te espera.' : 'Recupera tu acceso.',
            'intro' => $invitation
                ? "Te crearon una cuenta en {$appName}. Elige una contraseña para activarla y comenzar a trabajar con tu equipo."
                : 'Recibimos una solicitud para restablecer la contraseña de tu cuenta. Usa el siguiente botón para elegir una nueva.',
            'actionLabel' => $invitation ? 'Crear mi contraseña' : 'Restablecer contraseña',
            'actionUrl' => url(route($invitationFlow ? 'invitations.show' : 'password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], false)),
            'expiry' => "Este enlace vence en {$expiry} a partir de su emisión.",
            'nextStep' => $invitation
                ? 'Al guardar tu contraseña, tu cuenta quedará activa y este correo se marcará como verificado. Después podrás iniciar sesión.'
                : 'Después de guardar tu nueva contraseña, podrás iniciar sesión con ella. Este enlace solo se puede usar una vez.',
            'security' => $invitation
                ? 'Si no esperabas esta invitación, no uses el enlace y consulta con la persona que administra el sistema.'
                : 'Si no solicitaste este cambio, ignora este correo. Tu contraseña seguirá siendo la misma.',
        ]);
    }

    public function verification(User $user, string $url): MailMessage
    {
        $appName = config('app.name');
        $minutes = (int) config('auth.verification.expire', 60);

        return $this->message($user, [
            'subject' => "Confirma tu correo en {$appName}",
            'eyebrow' => 'SEGURIDAD DE TU CUENTA',
            'title' => 'Confirma que eres tú.',
            'intro' => "Confirma esta dirección de correo electrónico para seguir usando tu cuenta en {$appName}.",
            'actionLabel' => 'Verificar mi correo',
            'actionUrl' => $url,
            'expiry' => "Este enlace vence en {$minutes} minutos a partir de su emisión.",
            'nextStep' => 'Si el sistema te lo pide, inicia sesión con la cuenta asociada a este correo para completar la verificación.',
            'security' => 'Si no reconoces esta cuenta o este cambio de correo, no uses el enlace y consulta con la persona que administra el sistema.',
        ]);
    }

    private function message(User $user, array $content): MailMessage
    {
        return (new MailMessage)
            ->subject($content['subject'])
            ->view([
                'html' => 'emails.account.html',
                'text' => 'emails.account.text',
            ], array_merge($content, [
                'appName' => config('app.name'),
                'name' => $user->name,
                'email' => $user->email,
            ]));
    }
}
