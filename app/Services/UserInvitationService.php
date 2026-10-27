<?php

namespace App\Services;

use App\Enums\ActivityEvent;
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
    public function send(User $user, ?User $actor = null): void
    {
        abort_unless($user->status === UserStatus::Pending, 422, 'Solo se pueden reenviar invitaciones pendientes.');
        $teamId = $actor ? $actor->current_team_id : $user->current_team_id;
        $branchId = $actor ? $actor->current_sucursal_id : $user->current_sucursal_id;
        $resent = $user->invited_at !== null;
        try {
            $status = Password::broker('invitaciones')->sendResetLink(
                ['email' => $user->email, 'status' => UserStatus::Pending->value],
                fn ($recipient, $token) => $recipient->notify(new UserInvitation($token)),
            );
        } catch (TransportExceptionInterface $exception) {
            Password::broker('invitaciones')->deleteToken($user);
            $this->auditSend(ActivityEvent::InvitationFailed, $user, $actor, $teamId, $branchId, 'transport_failed');
            throw ValidationException::withMessages(['email' => 'No se pudo enviar la invitación. La cuenta sigue pendiente; puedes reenviarla desde el listado.']);
        } catch (\Throwable $exception) {
            $this->auditSend(ActivityEvent::InvitationFailed, $user, $actor, $teamId, $branchId, 'failed');
            throw $exception;
        }
        if ($status !== Password::RESET_LINK_SENT) {
            $this->auditSend($status === Password::RESET_THROTTLED ? ActivityEvent::InvitationThrottled : ActivityEvent::InvitationFailed,
                $user, $actor, $teamId, $branchId, $status === Password::RESET_THROTTLED ? 'throttled' : 'rejected');
            throw ValidationException::withMessages(['email' => $status === Password::RESET_THROTTLED
                ? 'Espera un minuto antes de reenviar la invitación.'
                : 'No se pudo enviar la invitación. La cuenta sigue pendiente; puedes reenviarla desde el listado.']);
        }
        // A resend also retires short-lived links issued by the previous flow.
        Password::broker('users')->deleteToken($user);
        $user->forceFill(['invited_at' => now()])->save();
        $this->auditSend($resent ? ActivityEvent::InvitationResent : ActivityEvent::InvitationSent, $user, $actor, $teamId, $branchId, 'transport_accepted');
    }

    private function auditSend(ActivityEvent $event, User $user, ?User $actor, ?int $teamId, ?int $branchId, string $result): void
    {
        app(ActivityLogService::class)->log($event, $event->label(), $user,
            ['state' => 'pending', 'result' => $result], $actor, $teamId, $branchId, inheritActorContext: false);
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
