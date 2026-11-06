<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use App\Services\Mail\AccountMailFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController::class,
            \App\Http\Controllers\TwoFactorSessionController::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        ResetPassword::toMailUsing(fn (User $user, string $token) => app(AccountMailFactory::class)->passwordReset($user, $token));
        VerifyEmail::toMailUsing(fn (User $user, string $url) => app(AccountMailFactory::class)->verification($user, $url));

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Failed::class,
            fn () => app(\App\Services\AccessAuditService::class)->anonymous(\App\Enums\ActivityEvent::LoginFailed));
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Lockout::class,
            fn () => app(\App\Services\AccessAuditService::class)->anonymous(\App\Enums\ActivityEvent::LoginThrottled));
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            if ($event->user && request()->routeIs('logout')) {
                app(\App\Services\ActivityLogService::class)->log(\App\Enums\ActivityEvent::Logout, 'Sesión cerrada', $event->user,
                    ['result' => 'completed'], $event->user, $event->user->current_team_id, $event->user->current_sucursal_id, inheritActorContext: false);
            }
        });
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey)->response(function ($request, $headers) {
                app(\App\Services\AccessAuditService::class)->anonymous(\App\Enums\ActivityEvent::LoginThrottled);

                return response('Ha realizado demasiados intentos. Espere un minuto e inténtelo nuevamente.', 429, $headers);
            });
        });
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'))->response(function ($request, $headers) {
                app(\App\Services\AccessAuditService::class)->anonymous(\App\Enums\ActivityEvent::TwoFactorThrottled);

                return response('Ha realizado demasiados intentos. Espere un minuto e inténtelo nuevamente.', 429, $headers);
            });
        });
    }
}
