<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('productos:activar-versiones')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('creditos:proyectar')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('comunicaciones:preparar-vencimientos')->hourly()->withoutOverlapping();
Schedule::command('sic:marcar-inciertas')->everyMinute()->withoutOverlapping();
Artisan::command('sic:marcar-inciertas', function () {
    $this->info('Intentos marcados inciertos: '.app(\App\Services\SICs\SicOperacionService::class)->marcarInciertas());
})->purpose('Recupera intentos SIC interrumpidos sin reenviar al proveedor');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('comunicaciones:preparar-vencimientos', function () {
    $prepared = app(\App\Services\CommunicationEventService::class)->reconcileDueReminders();
    $this->info("Recordatorios candidatos preparados: $prepared. Ningún mensaje fue enviado.");
})->purpose('Prepara eventos preventivos tres días antes del vencimiento, sin enviar mensajes');
