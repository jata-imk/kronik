<?php

namespace App\Providers;

use App\Interfaces\BrowserClientInterface;
use App\Services\Scrapers\BrowserClientService;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
