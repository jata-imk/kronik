<?php

use App\Models\User;

test('account deletion endpoint remains disabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete('/user', ['password' => 'password'])
        ->assertNotFound();

    expect($user->fresh())->not->toBeNull();
});
