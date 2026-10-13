<?php

use Illuminate\Support\Facades\Schedule;

// Programar el procesamiento automático de vacaciones anuales
Schedule::command('app:procesar-vacaciones-anuales')
    ->dailyAt('00:00')
    ->appendOutputTo(storage_path('logs/comandos.log'));

// Programar el procesamiento automático de finalizaciones de contrato a Plazo Fijo
Schedule::command('app:procesar-fin-contrato')
    ->dailyAt('00:00')
    ->appendOutputTo(storage_path('logs/comandos.log'));

// Programar el final de gestión
Schedule::command('app:procesar-fin-gestion')
    ->yearlyOn(12, 31, '23:59')
    ->appendOutputTo(storage_path('logs/comandos.log'));
