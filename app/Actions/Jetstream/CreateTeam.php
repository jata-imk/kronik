<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Events\AddingTeam;

class CreateTeam implements CreatesTeams
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  array<string, string>  $input
     */
    public function create(User $user, array $input): Team
    {
        Gate::forUser($user)->authorize('create', Team::class);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
        ])->validateWithBag('createTeam');

        AddingTeam::dispatch($user);
        $team = $user->ownedTeams()->create([
            'name' => $input['name'],
            'personal_team' => false,
            'activo' => true,
        ]);
        $user->switchTeam($team);

        if (! function_exists('setPermissionsTeamId')) {
            return $team;
        }

        // https://spatie.be/docs/laravel-permission/v6/basic-usage/teams-permissions#content-defining-a-super-admin-on-teams
        setPermissionsTeamId($team->id);

        /** @var \Spatie\Permission\Models\Role $roleModel */
        $roleModel = config('permission.models.role');
        $teamsKey = config('permission.column_names.team_foreign_key', 'team_id');
        $globalRoles = $roleModel::where($teamsKey, null)->where('name', '!=', 'Super Admin')->with('permissions')->get();
        foreach ($globalRoles as $role) {
            $teamRole = $roleModel::query()->create([
                'name' => $role->name,
                $teamsKey => $team->id,
                'guard_name' => $role->guard_name,
            ]);
            $teamRole->syncPermissions($role->permissions);
        }

        return $team;
    }
}
