<?php

use App\Http\Controllers\Api\Bot\BotTokenController;
use App\Http\Controllers\Api\Bot\EmpleadoController;
use App\Http\Controllers\Api\Integracion\EmpleadoController as IntegracionEmpleadoController;
use App\Http\Controllers\Api\Integracion\FeriadoController as IntegracionFeriadoController;
use App\Http\Controllers\Api\Integracion\SolicitudController as IntegracionSolicitudController;
use App\Http\Controllers\Api\Integracion\TokenController as IntegracionTokenController;
use Illuminate\Support\Facades\Route;

Route::post('v1/bot/auth/token', [BotTokenController::class, 'store'])
    ->middleware(['bot.client', 'throttle:6,1']);

Route::prefix('v1/bot')
    ->middleware(['auth:sanctum', 'abilities:bot:read'])
    ->group(function () {
        Route::get('empleados/por-telefono/{telefono}', [EmpleadoController::class, 'porTelefono']);
        Route::get('empleados/{empleado}/vacaciones', [EmpleadoController::class, 'vacaciones'])->whereNumber('empleado');
        Route::get('empleados/{empleado}/compensaciones', [EmpleadoController::class, 'compensaciones'])->whereNumber('empleado');
        Route::get('empleados/{empleado}/solicitudes-vacaciones', [EmpleadoController::class, 'solicitudesVacaciones'])->whereNumber('empleado');
        Route::get('empleados/{empleado}/solicitudes-compensaciones', [EmpleadoController::class, 'solicitudesCompensaciones'])->whereNumber('empleado');
    });

Route::post('v1/integracion/auth/token', [IntegracionTokenController::class, 'store'])
    ->middleware(['integracion.client', 'throttle:6,1']);

Route::prefix('v1/integracion')
    ->middleware(['auth:sanctum', 'abilities:integracion:read', 'throttle:60,1'])
    ->group(function () {
        Route::get('empleados', [IntegracionEmpleadoController::class, 'index']);
        Route::get('empleados/{empleado}', [IntegracionEmpleadoController::class, 'show'])->whereNumber('empleado');
        Route::get('feriados', [IntegracionFeriadoController::class, 'index']);
        Route::get('solicitudes-vacaciones', [IntegracionSolicitudController::class, 'vacaciones']);
        Route::get('solicitudes-compensaciones', [IntegracionSolicitudController::class, 'compensaciones']);
    });
