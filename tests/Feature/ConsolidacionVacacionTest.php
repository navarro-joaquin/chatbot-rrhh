<?php

use App\Models\Antiguedad;
use App\Models\ConsolidacionVacacion;
use App\Models\Empleado;
use App\Models\EmpleadoAntiguedad;
use App\Models\EmpleadoContrato;
use App\Services\VacacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Antiguedad::query()->create([
        'anios_desde' => 1,
        'anios_hasta' => 4,
        'dias_asignados' => 15,
    ]);

    Antiguedad::query()->create([
        'anios_desde' => 5,
        'anios_hasta' => 9,
        'dias_asignados' => 20,
    ]);
});

it('registra un historial cuando se crea una vacacion automatica', function () {
    $empleado = Empleado::create([
        'nombre_completo' => 'Test Historial',
        'carnet_identidad' => 'CI-HIST',
        'telefono' => '70000000',
        'correo_electronico' => 'hist@test.com',
        'estado' => true,
    ]);

    EmpleadoContrato::create([
        'empleado_id' => $empleado->id,
        'tipo' => 'Planta',
        'fecha_inicio' => '2023-01-03',
        'estado' => 'Vigente',
        'es_vigente' => true,
        'nro_item' => 'ITEM-HIST',
    ]);

    $service = app(VacacionService::class);

    // Procesa el primer aniversario
    $fechaProceso = Carbon::parse('2024-01-03');
    $service->procesarVacacionesAutomaticas($fechaProceso);

    $historial = ConsolidacionVacacion::where('empleado_id', $empleado->id)->first();

    expect($historial)->not->toBeNull()
        ->and($historial->dias_anadidos)->toBe('15.0')
        ->and($historial->accion)->toBe('creada')
        ->and($historial->origen)->toBe('contrato');
});

it('registra un historial cuando se actualiza una vacacion automatica', function () {
    $empleado = Empleado::create([
        'nombre_completo' => 'Test Historial Update',
        'carnet_identidad' => 'CI-HIST-UPD',
        'telefono' => '70000001',
        'correo_electronico' => 'histupd@test.com',
        'estado' => true,
    ]);

    EmpleadoContrato::create([
        'empleado_id' => $empleado->id,
        'tipo' => 'Planta',
        'fecha_inicio' => '2023-01-03',
        'estado' => 'Vigente',
        'es_vigente' => true,
        'nro_item' => 'ITEM-HIST-UPD',
    ]);

    $service = app(VacacionService::class);

    // 1. Primera consolidación (aniversario contrato)
    $service->procesarVacacionesAutomaticas(Carbon::parse('2024-01-03'));

    // 2. Registrar antigüedad reconocida que gatille una actualización en la misma gestión
    $contrato = $empleado->contratoVigente()->first();
    EmpleadoAntiguedad::create([
        'empleado_id' => $empleado->id,
        'contrato_id' => $contrato->id,
        'fecha_reconocida' => '2019-10-21',
        'vigencia_desde' => '2024-03-05',
        'origen' => 'Regularizacion',
        'vigente' => true,
    ]);

    // 3. Segunda consolidación (actualización)
    $service->procesarVacacionesAutomaticas(Carbon::parse('2024-10-21'));

    $historiales = ConsolidacionVacacion::where('empleado_id', $empleado->id)
        ->orderBy('id', 'asc')
        ->get();

    expect($historiales)->toHaveCount(2)
        ->and($historiales[0]->accion)->toBe('creada')
        ->and($historiales[1]->accion)->toBe('actualizada')
        ->and($historiales[1]->dias_anadidos)->toBe('20.0')
        ->and($historiales[1]->dias_totales_despues)->toBe('35.0');
});
