<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Jetstream;

use App\Actions\Jetstream\AddTeamMember;
use App\Actions\Jetstream\CreateTeam;
use App\Actions\Jetstream\DeleteTeam;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteTeamMember;
use App\Actions\Jetstream\RemoveTeamMember;
use App\Actions\Jetstream\UpdateTeamName;
use Illuminate\Support\Facades\Event;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Features;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::authenticateThrough(function (Request $request) {
            return array_filter([
                config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
                AttemptToAuthenticate::class,
                function (Request $request) {
                    // Registrar actividad de inicio de sesión usando Spatie
                    $user = $request->user();

                    if ($user) {
                        activity()
                            ->causedBy($user)
                            ->withProperties([
                                'ip' => $request->ip(),
                                'user_agent' => $request->header('User-Agent'),
                            ])
                            ->log('Inicio de sesión exitoso');
                    }
                },
                PrepareAuthenticatedSession::class,
            ]);
        });

        // Registrar actividad después de la autenticación de dos factores
        Event::listen(
            ValidTwoFactorAuthenticationCodeProvided::class,
            function ($event) {
                $request = request();
                $user = $event->user;

                if ($user) {
                    activity()
                        ->causedBy($user)
                        ->withProperties([
                            'ip' => $request->ip(),
                            'user_agent' => $request->header('User-Agent'),
                        ])
                        ->log('Autenticación de dos factores completada');
                }
            }
        );

        Jetstream::createTeamsUsing(CreateTeam::class);                 // spatie/laravel-permission: Validated ✅
        Jetstream::updateTeamNamesUsing(UpdateTeamName::class);         // spatie/laravel-permission: Validated ✅
        Jetstream::addTeamMembersUsing(AddTeamMember::class);           // spatie/laravel-permission: Validated ✅
        Jetstream::inviteTeamMembersUsing(InviteTeamMember::class);     // spatie/laravel-permission: Validated ✅
        Jetstream::removeTeamMembersUsing(RemoveTeamMember::class);     // spatie/laravel-permission: Pending 🕑
        Jetstream::deleteTeamsUsing(DeleteTeam::class);                 // spatie/laravel-permission: Pending 🕑
        Jetstream::deleteUsersUsing(DeleteUser::class);                 // spatie/laravel-permission: Pending 🕑
    }
}
