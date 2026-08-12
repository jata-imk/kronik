<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\DeletesTeams;

class DeleteTeam implements DeletesTeams
{
    /**
     * Delete the given team.
     */
    public function delete(Team $team): void
    {
        if ($team->currentUsers()->exists()) {
            throw ValidationException::withMessages([
                'team' => 'Cambia el equipo actual de todos sus usuarios antes de desactivarlo.',
            ]);
        }

        $team->update(['activo' => false]);
    }
}
