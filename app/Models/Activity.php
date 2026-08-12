<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            $user = Auth::user();

            if (! $user instanceof User && $activity->causer_type === (new User)->getMorphClass()) {
                $user = User::find($activity->causer_id);
            }

            if ($user instanceof User && $user->current_team_id) {
                $activity->team_id ??= $user->current_team_id;
                $activity->sucursal_id ??= $user->current_sucursal_id;
            }
        });
    }
}
