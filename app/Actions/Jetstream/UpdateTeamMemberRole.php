<?php

namespace App\Actions\Jetstream;

use App\Enums\ActivityEvent;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Laravel\Jetstream\Jetstream;

class UpdateTeamMemberRole extends \Laravel\Jetstream\Actions\UpdateTeamMemberRole
{
    public function update($user, $team, $teamMemberId, string $role): void
    {
        $branchId = $user->current_sucursal_id;
        DB::transaction(function () use ($user, $team, $teamMemberId, $role, $branchId) {
            $before = $team->users()->whereKey($teamMemberId)->first()?->membership?->role;
            parent::update($user, $team, $teamMemberId, $role);
            $after = $team->users()->whereKey($teamMemberId)->first()?->membership?->role;
            if ($after !== null && $before !== $after) {
                app(ActivityLogService::class)->log(ActivityEvent::TeamMemberLegacyRoleUpdated,
                    ActivityEvent::TeamMemberLegacyRoleUpdated->label(), Jetstream::findUserByIdOrFail($teamMemberId),
                    ['changed_fields' => ['membership.role'], 'result' => 'applied'], $user, $team->id, $branchId, inheritActorContext: false);
            }
        });
    }
}
