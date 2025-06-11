<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use Laravel\Jetstream\Contracts\DeletesTeams;

class DeleteTeam implements DeletesTeams
{
    /**
     * Delete the given team.
     */
    public function delete(Team $team): void
    {
        // delete role and permissions related to the team
        $roleModel = config('permission.models.role');

        $rolesTeam = $roleModel::where(config('permission.column_names.team_foreign_key', 'team_id'), $team->id)->get();

        foreach ($rolesTeam as $role) {
            $role->permissions()->detach();
            $role->delete();
        }

        $team->purge();
    }
}
