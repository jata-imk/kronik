<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\OperatorNotificationEvent;
use App\Models\OperatorNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OperatorNotificationService
{
    public function publish(
        User $recipient,
        int $teamId,
        int $sucursalId,
        OperatorNotificationEvent $event,
        string $dedupKey,
        string $title,
        string $body,
    ): ?OperatorNotification {
        $belongsToTeam = $recipient->ownedTeams()->whereKey($teamId)->exists()
            || $recipient->teams()->whereKey($teamId)->exists();
        if (! $belongsToTeam || ! $recipient->sucursales()->whereKey($sucursalId)->exists()) {
            return null;
        }

        return OperatorNotification::query()->createOrFirst(
            ['team_id' => $teamId, 'user_id' => $recipient->id, 'dedup_key' => $dedupKey],
            [
                'sucursal_id' => $sucursalId,
                'event' => $event->value,
                'priority' => $event->priority(),
                'title' => $title,
                'body' => $body,
            ],
        );
    }

    public function visibleTo(User $user): Builder
    {
        return OperatorNotification::query()
            ->where('user_id', $user->id)
            ->where('team_id', $user->current_team_id)
            ->where('sucursal_id', $user->current_sucursal_id);
    }

    public function summary(User $user): array
    {
        $query = $this->visibleTo($user);

        return [
            'unread_count' => (clone $query)->whereNull('read_at')->count(),
            'items' => (clone $query)->latest()->limit(6)->get(),
        ];
    }

    public function markRead(User $user, int $notificationId): OperatorNotification
    {
        return DB::transaction(function () use ($user, $notificationId) {
            $notification = $this->visibleTo($user)->lockForUpdate()->findOrFail($notificationId);
            if ($notification->read_at === null) {
                $notification->forceFill(['read_at' => now()])->save();
                app(ActivityLogService::class)->log(
                    ActivityEvent::OperatorNotificationRead,
                    'Notificación interna leída',
                    subject: $notification,
                    metadata: ['result' => 'read'],
                    causer: $user,
                    teamId: $notification->team_id,
                    sucursalId: $notification->sucursal_id,
                    inheritActorContext: false,
                );
            }

            return $notification;
        });
    }
}
