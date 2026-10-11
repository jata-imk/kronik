<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('productos:activar-versiones')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('sic:marcar-inciertas')->everyMinute()->withoutOverlapping();
Artisan::command('sic:marcar-inciertas', function () {
    $this->info('Intentos marcados inciertos: '.app(\App\Services\SICs\SicOperacionService::class)->marcarInciertas());
})->purpose('Recupera intentos SIC interrumpidos sin reenviar al proveedor');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
