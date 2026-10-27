<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserAccessAuditService
{
    public function snapshot(User $user): array
    {
        return [
            'teams' => $user->ownedTeams()->pluck('id')->merge($user->teams()->pluck('teams.id'))->unique()->map(fn ($id) => (int) $id)->all(),
            'roles' => DB::table('model_has_roles')->where('model_type', $user->getMorphClass())->where('model_id', $user->id)
                ->orderBy('role_id')->get(['team_id', 'role_id'])->groupBy('team_id')
                ->map(fn ($roles) => $roles->pluck('role_id')->map(fn ($id) => (int) $id)->all())->all(),
        ];
    }

    public function recordChanges(User $user, array $before, User $actor, ?int $originalBranchId): void
    {
        $after = $this->snapshot($user);
        foreach (array_unique([...$before['teams'], ...$after['teams']]) as $teamId) {
            $wasMember = in_array($teamId, $before['teams'], true);
            $isMember = in_array($teamId, $after['teams'], true);
            $event = match (true) {
                ! $wasMember && $isMember => ActivityEvent::TeamMemberAdded,
                $wasMember && ! $isMember => ActivityEvent::TeamMemberRemoved,
                ($before['roles'][$teamId] ?? []) !== ($after['roles'][$teamId] ?? []) => ActivityEvent::UserTeamRolesUpdated,
                default => null,
            };
            if ($event) {
                app(ActivityLogService::class)->log($event, $event->label(), $user, [
                    'changed_fields' => [$wasMember !== $isMember ? 'team_memberships' : 'role_ids'],
                    'related' => ['type' => 'teams', 'id' => $teamId],
                    'result' => 'applied',
                ], $actor, $teamId, $originalBranchId, inheritActorContext: false);
            }
        }
    }
}
