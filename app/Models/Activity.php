<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            if ($activity->team_id !== null) {
                return;
            }

            $user = Auth::user();

            if (! $user instanceof User && $activity->causer_type === (new User)->getMorphClass()) {
                $user = User::find($activity->causer_id);
            }

            if ($user instanceof User && $user->current_team_id) {
                $activity->team_id = $user->current_team_id;
            }
        });
    }
}
