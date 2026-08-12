<?php

use App\Enums\UserStatus;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors([
        'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
    ]);

    $this->assertGuest();
});

test('inactive users cannot authenticate with a valid password', function () {
    $user = User::factory()->create(['status' => UserStatus::Inactive]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors([
        'email' => 'Tu cuenta no está activa. Solicita ayuda a un administrador.',
    ]);

    $this->assertGuest();
});
