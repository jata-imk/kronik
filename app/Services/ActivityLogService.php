<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class ActivityLogService
{
    public function log(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $causer = null,
        ?int $teamId = null,
    ): ?ActivityContract {
        $teamId ??= $causer?->current_team_id;

        $logger = activity()
            ->event($event)
            ->withProperties($properties)
            ->tap(function ($activity) use ($teamId): void {
                $activity->team_id = $teamId;
            });

        if ($subject) {
            $logger->performedOn($subject);
        }

        if ($causer) {
            $logger->causedBy($causer);
        }

        return $logger->log($description);
    }
}
