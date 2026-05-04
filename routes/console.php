<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Programar el procesamiento automático de vacaciones anuales
Schedule::command('app:procesar-vacaciones-anuales')
    ->dailyAt('00:00')
    ->sendOutputTo(storage_path('logs/comandos.log'));

// Programar el procesamiento automático de finalizaciones de contrato Eventual
Schedule::command('app:procesar-fin-contrato')
    ->dailyAt('00:00')
    ->sendOutputTo(storage_path('logs/comandos.log'));

// Programar el final de gestión
Schedule::command('app:procesar-fin-gestion')
    ->yearlyOn(12, 31, '23:59')
    ->sendOutputTo(storage_path('logs/comandos.log'));
