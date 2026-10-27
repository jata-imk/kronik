<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\UserInvitation;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\CompletePasswordReset;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class UserInvitationService
{
    public function send(User $user): void
    {
        abort_unless($user->status === UserStatus::Pending, 422, 'Solo se pueden reenviar invitaciones pendientes.');
        try {
            $status = Password::broker('invitaciones')->sendResetLink(
                ['email' => $user->email, 'status' => UserStatus::Pending->value],
                fn ($recipient, $token) => $recipient->notify(new UserInvitation($token)),
            );
        } catch (TransportExceptionInterface $exception) {
            Password::broker('invitaciones')->deleteToken($user);
            throw ValidationException::withMessages(['email' => 'No se pudo enviar la invitación. La cuenta sigue pendiente; puedes reenviarla desde el listado.']);
        }
        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => $status === Password::RESET_THROTTLED
                ? 'Espera un minuto antes de reenviar la invitación.'
                : 'No se pudo enviar la invitación. La cuenta sigue pendiente; puedes reenviarla desde el listado.']);
        }
        // A resend also retires short-lived links issued by the previous flow.
        Password::broker('users')->deleteToken($user);
        $user->forceFill(['invited_at' => now()])->save();
    }

    public function accept(array $input): string
    {
        return DB::transaction(function () use ($input) {
            $user = User::where('email', $input['email'])->lockForUpdate()->first();
            if (! $user || $user->status !== UserStatus::Pending) {
                return Password::INVALID_TOKEN;
            }

            return Password::broker('invitaciones')->reset(
                [...$input, 'status' => UserStatus::Pending->value],
                function ($recipient) use ($input) {
                    app(ResetsUserPasswords::class)->reset($recipient, $input);
                    app(CompletePasswordReset::class)(app(StatefulGuard::class), $recipient);
                },
            );
        });
    }

    public function details(User $user): array
    {
        return $this->detailsFor(collect([$user]))->get($user->id);
    }

    public function detailsFor(Collection $users): Collection
    {
        $emails = $users->filter(fn (User $user) => $user->status === UserStatus::Pending)->pluck('email');
        $issued = [];
        if ($emails->isNotEmpty()) {
            foreach (['invitaciones', 'users'] as $broker) {
                $issued[$broker] = DB::table(config("auth.passwords.{$broker}.table"))->whereIn('email', $emails)
                    ->pluck('created_at', 'email')->all();
            }
        }

        return $users->mapWithKeys(fn (User $user) => [$user->id => $this->formatDetails($user, $issued)]);
    }

    private function formatDetails(User $user, array $issued): array
    {
        $expiresAt = null;
        if ($user->status === UserStatus::Pending) {
            foreach (['invitaciones', 'users'] as $broker) {
                $issuedAt = $issued[$broker][$user->email] ?? null;
                if ($issuedAt) {
                    $candidate = Carbon::parse($issuedAt)->addMinutes((int) config("auth.passwords.{$broker}.expire"));
                    if (! $expiresAt || $candidate->greaterThan($expiresAt)) {
                        $expiresAt = $candidate;
                    }
                }
            }
        }

        return [
            'invited_at' => $user->invited_at?->toIso8601String(),
            'invitation_expires_at' => $expiresAt?->toIso8601String(),
            'invitation_expired' => $user->status === UserStatus::Pending && (! $expiresAt || $expiresAt->isPast()),
        ];
    }
}
