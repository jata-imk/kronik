<?php

use App\Models\User;

test('teams can be created', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $user->forceFill(['is_super_admin' => true])->save();
    $this->actingAs($user);

    $this->post('/teams', [
        'name' => 'Test Team',
    ]);

    expect($user->fresh()->ownedTeams)->toHaveCount(2);
    expect($user->fresh()->ownedTeams()->latest('id')->first()->name)->toEqual('Test Team');
});
