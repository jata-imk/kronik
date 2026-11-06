<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\User;

class AccessAuditService
{
    public function anonymous(ActivityEvent $event): void
    {
        // One HTTP outcome can be reported by both the guard and Fortify.
        $key = 'audit.'.$event->value;
        if (request()->attributes->get($key)) {
            return;
        }
        app(ActivityLogService::class)->log($event, $event->label(), metadata: ['result' => 'rejected'], inheritActorContext: false);
        request()->attributes->set($key, true);
    }

    public function authenticated(ActivityEvent $event, User $user): void
    {
        try {
            app(ActivityLogService::class)->log($event, $event->label(), $user, ['result' => 'completed'],
                $user, $user->current_team_id, $user->current_sucursal_id, inheritActorContext: false);
        } catch (\Throwable $exception) {
            auth()->guard(config('fortify.guard'))->logout();
            throw $exception;
        }
    }
}
