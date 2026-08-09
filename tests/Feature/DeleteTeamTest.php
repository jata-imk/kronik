<?php

use App\Models\Team;
use App\Models\User;

test('teams are deactivated without deleting memberships', function () {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    $user->ownedTeams()->save($team = Team::factory()->make([
        'personal_team' => false,
    ]));

    $team->users()->attach(
        $otherUser = User::factory()->create(), ['role' => 'test-role']
    );

    $this->delete('/teams/'.$team->id);

    expect($team->fresh()->activo)->toBeFalse();
    expect($otherUser->fresh()->teams)->toHaveCount(1);
});

test('personal teams cant be deleted', function () {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    $this->delete('/teams/'.$user->currentTeam->id);

    expect($user->currentTeam->fresh())->not->toBeNull();
});

test('teams with current users cannot be deactivated', function () {
    $this->actingAs($owner = User::factory()->withPersonalTeam()->create());
    $team = $owner->ownedTeams()->create(['name' => 'Operaciones', 'personal_team' => false]);
    $member = User::factory()->create(['current_team_id' => $team->id]);
    $team->users()->attach($member);

    $this->delete('/teams/'.$team->id)->assertSessionHasErrors('team');

    expect($team->fresh()->activo)->toBeTrue();
});
