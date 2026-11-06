<?php

namespace App\Actions\Fortify;

use App\Enums\ActivityEvent;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ])->validateWithBag('updateProfileInformation');

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $input) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($user->name === $input['name'] && $user->email === $input['email'] && ! isset($input['photo'])) {
                return;
            }
            if (isset($input['photo'])) {
                $user->updateProfilePhoto($input['photo']);
            }

            if (
                $input['email'] !== $user->email &&
                $user instanceof MustVerifyEmail
            ) {
                $this->updateVerifiedUser($user, $input);
            } else {
                $user->forceFill([
                    'name' => $input['name'],
                    'email' => $input['email'],
                ])->save();
            }

            app(ActivityLogService::class)->log(
                event: ActivityEvent::UserProfileUpdated,
                description: 'Perfil actualizado',
                subject: $user,
                metadata: ['changed_fields' => array_values(array_intersect(['name', 'email', 'photo'], array_keys($input)))],
                causer: $user,
                teamId: $user->current_team_id, sucursalId: $user->current_sucursal_id, inheritActorContext: false,
            );
        });
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        \Illuminate\Support\Facades\DB::afterCommit(fn () => $user->sendEmailVerificationNotification());
    }
}
