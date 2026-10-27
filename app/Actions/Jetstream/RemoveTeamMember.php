<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\RemovesTeamMembers;
use Laravel\Jetstream\Events\TeamMemberRemoved;

class RemoveTeamMember implements RemovesTeamMembers
{
    /**
     * Remove the team member from the given team.
     */
    public function remove(User $user, Team $team, User $teamMember): void
    {
        $this->authorize($user, $team, $teamMember);

        $branchId = $user->current_sucursal_id;
        DB::transaction(function () use ($user, $team, $teamMember, $branchId) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            $teamMember = User::whereKey($teamMember->id)->lockForUpdate()->firstOrFail();
            $this->ensureUserDoesNotOwnTeam($teamMember, $team);
            abort_unless($team->users()->whereKey($teamMember->id)->exists(), 404);
            $alternative = Team::where('activo', true)->where('id', '!=', $team->id)
                ->where(fn ($query) => $query->where('user_id', $teamMember->id)
                    ->orWhereHas('users', fn ($users) => $users->whereKey($teamMember->id)))->exists();
            if ((int) $teamMember->current_team_id === (int) $team->id || ! $alternative) {
                throw ValidationException::withMessages(['team' => 'Asigna otro equipo activo y cambia el equipo actual del usuario antes de quitarlo de este equipo.'])->errorBag('removeTeamMember');
            }
            $before = app(\App\Services\UserAccessAuditService::class)->snapshot($teamMember);
            $team->removeUser($teamMember);
            foreach (['model_has_roles', 'model_has_permissions'] as $table) {
                DB::table(config("permission.table_names.{$table}"))->where('team_id', $team->id)
                    ->where('model_id', $teamMember->id)->where('model_type', $teamMember->getMorphClass())->delete();
            }
            app(\App\Services\UserAccessAuditService::class)->recordChanges($teamMember, $before, $user, $branchId);
            TeamMemberRemoved::dispatch($team, $teamMember);
        });
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $teamMember->unsetRelation('roles')->unsetRelation('permissions');
    }

    /**
     * Authorize that the user can remove the team member.
     */
    protected function authorize(User $user, Team $team, User $teamMember): void
    {
        if (! Gate::forUser($user)->check('removeTeamMember', $team) &&
            $user->id !== $teamMember->id) {
            throw new AuthorizationException;
        }
    }

    /**
     * Ensure that the currently authenticated user does not own the team.
     */
    protected function ensureUserDoesNotOwnTeam(User $teamMember, Team $team): void
    {
        if ($teamMember->id === $team->owner->id) {
            throw ValidationException::withMessages([
                'team' => ['Transfiere la responsabilidad antes de quitar al responsable del equipo.'],
            ])->errorBag('removeTeamMember');
        }
    }
}
