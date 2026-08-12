<?php

namespace App\Providers;

use App\Actions\Fortify\EnsureUserIsActive;
use App\Actions\Jetstream\AddTeamMember;
use App\Actions\Jetstream\CreateTeam;
use App\Actions\Jetstream\DeleteTeam;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteTeamMember;
use App\Actions\Jetstream\RemoveTeamMember;
use App\Actions\Jetstream\UpdateTeamName;
use App\Enums\ActivityEvent;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Jetstream;

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
        Jetstream::defaultApiTokenPermissions(['read']);
        Jetstream::permissions([
            'create',
            'read',
            'update',
            'delete',
        ]);
        Jetstream::role('admin', 'Administrator', [
            'create',
            'read',
            'update',
            'delete',
        ])->description('Administrator users can perform any action.');

        Jetstream::role('editor', 'Editor', [
            'read',
            'create',
            'update',
        ])->description('Editor users can read, create, and update records.');

        Fortify::authenticateThrough(function (Request $request) {
            return array_filter([
                config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
                AttemptToAuthenticate::class,
                EnsureUserIsActive::class,
                function (Request $request, $next) {
                    $user = $request->user();

                    if ($user) {
                        app(ActivityLogService::class)->log(
                            event: ActivityEvent::Login,
                            description: 'Inicio de sesion exitoso',
                            causer: $user,
                        );
                    }

                    return $next($request);
                },
                PrepareAuthenticatedSession::class,
            ]);
        });

        Event::listen(
            ValidTwoFactorAuthenticationCodeProvided::class,
            function ($event) {
                $user = $event->user;

                if ($user) {
                    app(ActivityLogService::class)->log(
                        event: ActivityEvent::TwoFactorCompleted,
                        description: 'Autenticacion de dos factores completada',
                        causer: $user,
                    );
                }
            },
        );

        Jetstream::createTeamsUsing(CreateTeam::class);
        Jetstream::updateTeamNamesUsing(UpdateTeamName::class);
        Jetstream::addTeamMembersUsing(AddTeamMember::class);
        Jetstream::inviteTeamMembersUsing(InviteTeamMember::class);
        Jetstream::removeTeamMembersUsing(RemoveTeamMember::class);
        Jetstream::deleteTeamsUsing(DeleteTeam::class);
        Jetstream::deleteUsersUsing(DeleteUser::class);
    }
}
