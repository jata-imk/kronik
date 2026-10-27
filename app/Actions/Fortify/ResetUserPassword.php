<?php

namespace App\Actions\Fortify;

use App\Enums\ActivityEvent;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $attributes = ['password' => Hash::make($input['password'])];

        if ($user->status === UserStatus::Pending) {
            $attributes['status'] = UserStatus::Active;
            $attributes['activated_at'] = now();
            $attributes['email_verified_at'] = $user->email_verified_at ?? now();
        }

        $event = $user->status === UserStatus::Pending ? ActivityEvent::UserActivated : ActivityEvent::PasswordRecovered;
        $teamId = $user->current_team_id;
        $branchId = $user->current_sucursal_id;
        DB::transaction(function () use ($user, $attributes, $event, $teamId, $branchId) {
            $user->forceFill($attributes)->save();
            // Successful activation or recovery consumes links from both flows.
            \Illuminate\Support\Facades\Password::broker('invitaciones')->deleteToken($user);
            \Illuminate\Support\Facades\Password::broker('users')->deleteToken($user);
            app(ActivityLogService::class)->log($event, $event->label(), $user,
                ['result' => 'applied', 'state' => $user->status->value], $user, $teamId, $branchId, inheritActorContext: false);
        });
    }
}
