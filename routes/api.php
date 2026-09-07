<?php

use App\Http\Controllers\Api\Bot\BotTokenController;
use App\Http\Controllers\Api\Bot\EmpleadoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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
