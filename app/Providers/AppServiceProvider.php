<?php

namespace App\Providers;

use App\Interfaces\BrowserClientInterface;
use App\Interfaces\GeocodingServiceInterface;
use App\Models\Cliente;
use App\Models\Permission;
use App\Models\ProductoCrediticio;
use App\Models\ProductoVersion;
use App\Policies\ProductoCrediticioPolicy;
use App\Policies\ProductoVersionPolicy;
use App\Services\GeocodingService;
use App\Services\Scrapers\BrowserClientService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
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
        Gate::policy(ProductoCrediticio::class, ProductoCrediticioPolicy::class);
        Gate::policy(ProductoVersion::class, ProductoVersionPolicy::class);
        Relation::morphMap([
            'clientes' => Cliente::class,
        ]);

        // Global administrators bypass team-scoped permissions.
        Gate::before(function ($user, $ability) {
            return $user->is_super_admin ? true : null;
        });

        // `artisan test` boots the application once before PHPUnit applies its
        // isolated database environment. Avoid touching the configured app DB.
        if ($this->app->runningInConsole() && ($_SERVER['argv'][1] ?? null) === 'test') {
            return;
        }

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
