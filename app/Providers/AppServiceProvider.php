<?php

namespace App\Providers;

use App\Interfaces\BrowserClientInterface;
use App\Interfaces\GeocodingServiceInterface;
use App\Services\GeocodingService;
use App\Services\Scrapers\BrowserClientService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\Permission;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HttpClientInterface::class, function () {
            return HttpClient::create([
                'timeout' => 60,
                'verify_host' => false,
            ]);
        });
        $this->app->bind(BrowserClientInterface::class, BrowserClientService::class);
        $this->app->bind(GeocodingServiceInterface::class, GeocodingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        // Checks for table existence before registering policies
        if (Schema::hasTable('permissions')) {
            $permissions = Permission::all();

            foreach ($permissions as $permission) {
                Gate::define($permission->name, function ($user) use ($permission) {
                    return $user->hasPermissionTo($permission->name);
                });
            }
        }
    }
}
