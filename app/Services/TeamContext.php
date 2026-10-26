<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class TeamContext
{
    public function resolve(Request $request): Team
    {
        if ($team = $request->attributes->get('selectedRoleTeam')) {
            return $team;
        }
        $id = $request->query('team', $request->user()->current_team_id);
        abort_unless($id, 403);
        Validator::make(['team' => $id], ['team' => ['required', 'integer']], [], ['team' => 'equipo seleccionado'])->validate();
        $team = Team::findOrFail($id);
        $this->ensureAccess($request->user(), $team);
        $request->attributes->set('selectedRoleTeam', $team);

        return $team;
    }

    public function ensureAccess(User $user, Team $team): void
    {
        abort_unless($user->is_super_admin || $user->belongsToTeam($team), 403);
    }

    public function run(User $user, Team $team, callable $operation): mixed
    {
        $this->ensureAccess($user, $team);
        $previous = getPermissionsTeamId();
        $relations = array_intersect_key($user->getRelations(), array_flip(['roles', 'permissions']));
        try {
            setPermissionsTeamId($team->id);
            $user->unsetRelation('roles')->unsetRelation('permissions');

            return $operation();
        } finally {
            setPermissionsTeamId($previous);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            foreach ($relations as $name => $value) {
                $user->setRelation($name, $value);
            }
        }
    }

    public function allows(User $user, Team $team, string $permission): bool
    {
        if (! $user->is_super_admin && ! $user->belongsToTeam($team)) {
            return false;
        }

        return $this->run($user, $team, fn () => $user->can($permission));
    }

    public function authorize(User $user, Team $team, string $permission): void
    {
        $this->run($user, $team, fn () => Gate::forUser($user)->authorize($permission));
    }
}
