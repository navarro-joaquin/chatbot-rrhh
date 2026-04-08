<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'pages.auth.login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('gestiones', 'gestiones')->name('gestiones.index');
    Route::livewire('antiguedades', 'antiguedades')->name('antiguedades.index');
    Route::livewire('empleados', 'empleados')->name('empleados.index');
    Route::livewire('empleados/{id}', 'empleado-detalle')->name('empleados.show');
    Route::livewire('usuarios', 'users')->name('users.index');
    Route::livewire('vacaciones', 'vacaciones')->name('vacaciones.index');
    Route::livewire('solicitudes-vacaciones', 'solicitudes-vacaciones')->name('solicitudes-vacaciones.index');
    Route::livewire('compensaciones', 'compensaciones')->name('compensaciones.index');
    Route::livewire('actividades', 'actividades')->name('actividades.index');
    Route::livewire('feriados', 'feriados')->name('feriados.index');
});



require __DIR__.'/settings.php';
