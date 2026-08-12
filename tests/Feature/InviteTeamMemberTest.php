<?php

use App\Models\User;

test('generic Jetstream member endpoint cannot invite an unknown account', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user)
        ->post('/teams/'.$user->currentTeam->id.'/members', [
            'email' => 'invitado@example.test',
            'role' => 'admin',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['email'], null, 'addTeamMember');

    expect($user->currentTeam->fresh()->teamInvitations)->toHaveCount(0);
});

test('generic Jetstream invitation cancellation endpoint remains disabled', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user)
        ->delete('/team-invitations/999')
        ->assertNotFound();
});
