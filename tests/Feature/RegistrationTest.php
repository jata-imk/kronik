<?php

test('self registration remains disabled for both page and submission', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Registro no autorizado',
        'email' => 'registro@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'registro@example.test']);
});
