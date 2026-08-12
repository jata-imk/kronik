<?php

namespace App\Actions\Fortify;

use App\Enums\UserStatus;
use App\Models\User;
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

        $user->forceFill($attributes)->save();
    }
}
