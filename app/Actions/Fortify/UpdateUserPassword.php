<?php

namespace App\Actions\Fortify;

use App\Enums\ActivityEvent;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        $teamId = $user->current_team_id;
        $branchId = $user->current_sucursal_id;
        DB::transaction(function () use ($user, $input, $teamId, $branchId) {
            $user->forceFill([
                'password' => Hash::make($input['password']),
            ])->save();
            app(ActivityLogService::class)->log(ActivityEvent::PasswordChanged, ActivityEvent::PasswordChanged->label(), $user,
                ['result' => 'applied'], $user, $teamId, $branchId, inheritActorContext: false);
        });
    }
}
