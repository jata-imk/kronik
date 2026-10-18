<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('preview writes three synthetic multipart samples without sending or creating users', function () {
    Storage::fake('local');
    Mail::fake();
    Notification::fake();
    config(['app.name' => 'Kronik QA']);

    $this->artisan('mail:preview')->assertSuccessful();

    foreach (['invitacion', 'recuperacion', 'verificacion'] as $name) {
        foreach (['html', 'txt', 'eml'] as $extension) {
            Storage::disk('local')->assertExists("mail-previews/{$name}.{$extension}");
        }
        expect(Storage::disk('local')->get("mail-previews/{$name}.eml"))
            ->toContain('multipart/alternative', 'text/plain', 'text/html');
        expect(Storage::disk('local')->get("mail-previews/{$name}.html"))
            ->toContain('Kronik QA', 'Andrea Martínez', 'preview.example.invalid', 'SOLO-VISTA-PREVIA')
            ->not->toContain('signature=');
    }

    expect(User::count())->toBe(0);
    Mail::assertNothingSent();
    Notification::assertNothingSent();
});

test('preview cannot generate files outside development environments', function () {
    Storage::fake('local');
    $this->app->detectEnvironment(fn () => 'production');

    $this->artisan('mail:preview')
        ->expectsOutput('Las vistas previas solo se pueden generar en local o testing.')
        ->assertFailed();

    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});
