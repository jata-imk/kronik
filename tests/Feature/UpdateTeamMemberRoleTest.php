<?php

use App\Models\User;

test('team member roles can be updated', function () {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());
    grantTeamPermissions($user, $user->currentTeam, ['update members teams']);

    $user->currentTeam->users()->attach(
        $otherUser = User::factory()->create(), ['role' => 'admin']
    );

    $this->put('/teams/'.$user->currentTeam->id.'/members/'.$otherUser->id, [
        'role' => 'editor',
    ]);

    expect($otherUser->fresh()->hasTeamRole(
        $user->currentTeam->fresh(), 'editor'
    ))->toBeTrue();
});

test('team membership alone cannot update team member roles', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $user->currentTeam->users()->attach(
        $otherUser = User::factory()->create(), ['role' => 'admin']
    );

    $this->actingAs($otherUser);

    $this->put('/teams/'.$user->currentTeam->id.'/members/'.$otherUser->id, [
        'role' => 'editor',
    ]);

    expect($otherUser->fresh()->hasTeamRole(
        $user->currentTeam->fresh(), 'admin'
    ))->toBeTrue();
});
