<?php

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Feriado;
use App\Models\Gestion;
use App\Models\SolicitudVacacion;
use App\Models\User;
use App\Models\Vacacion;
use Illuminate\Support\Carbon;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('authenticated users can see dashboard statistics', function () {
    Carbon::setTestNow('2026-04-21');

    $user = User::factory()->create();
    $gestion = Gestion::create(['anio' => 2026]);

    $empleadoActivo = Empleado::factory()->create([
        'nombre_completo' => 'Ana Perez',
        'estado' => true,
    ]);

    $empleadoSinContrato = Empleado::factory()->create([
        'nombre_completo' => 'Luis Flores',
        'estado' => true,
    ]);

    $contrato = EmpleadoContrato::create([
        'empleado_id' => $empleadoActivo->id,
        'tipo' => 'Planta',
        'numero_contrato' => 'C-001',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-05-10',
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);

    Vacacion::create([
        'empleado_id' => $empleadoActivo->id,
        'gestion_id' => $gestion->id,
        'dias_disponibles' => 12.5,
    ]);

    SolicitudVacacion::create([
        'empleado_id' => $empleadoActivo->id,
        'fecha_inicio' => '2026-04-25',
        'fecha_fin' => '2026-04-27',
        'dias_solicitados' => 3,
        'estado' => 'aprobado',
    ]);

    Compensacion::create([
        'empleado_id' => $empleadoActivo->id,
        'gestion_id' => $gestion->id,
        'contrato_id' => $contrato->id,
        'cantidad_horas' => 8,
        'fecha_registro' => '2026-04-20',
        'estado' => 'disponible',
    ]);

    Feriado::create([
        'nombre' => 'Dia del Trabajo',
        'fecha' => '2026-05-01',
        'gestion_id' => $gestion->id,
        'estado' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Dashboard operativo');
    $response->assertSee('Empleados activos');
    $response->assertSee('2');
    $response->assertSee('12.5');
    $response->assertSee('8.0');
    $response->assertSee('Ana Perez');
    $response->assertSee('Dia del Trabajo');

    Carbon::setTestNow();
});
