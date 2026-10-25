<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\UserStatus;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamManagementService
{
    public function update(Team $team, array $data): void
    {
        DB::transaction(function () use ($team, $data) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            if (array_key_exists('activo', $data) && ! $data['activo'] && $team->currentUsers()->exists()) {
                throw ValidationException::withMessages(['team' => 'Cambia el equipo actual de todos sus usuarios antes de desactivarlo.']);
            }
            $team->update($data);
        });
    }

    public function transfer(User $actor, Team $team, int $responsibleId): void
    {
        app(TeamContext::class)->authorize($actor, $team, 'update teams');
        DB::transaction(function () use ($actor, $team, $responsibleId) {
            $team = Team::whereKey($team->id)->lockForUpdate()->firstOrFail();
            $new = User::whereKey($responsibleId)->lockForUpdate()->firstOrFail();
            if ($new->id === $team->user_id || $new->status !== UserStatus::Active || ! $new->belongsToTeam($team)) {
                throw ValidationException::withMessages(['responsible_id' => 'Selecciona otro integrante activo de este equipo como responsable.']);
            }
            $oldId = $team->user_id;
            $team->users()->syncWithoutDetaching([$oldId]);
            $team->users()->detach($new->id);
            $team->forceFill(['user_id' => $new->id])->save();
            app(ActivityLogService::class)->log(
                ActivityEvent::TeamResponsibleTransferred,
                "Responsable transferido del usuario {$oldId} al usuario {$new->id}.",
                $team, ['changed_fields' => ['user_id'], 'related' => ['type' => 'users', 'id' => $new->id]],
                $actor, $team->id,
            );
        });
    }
}
