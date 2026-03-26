<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\Gestion;
use App\Models\Vacacion;
use App\Services\VacacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('descuenta días de vacación usando el método FIFO', function () {
    // 1. Crear un empleado
    $empleado = Empleado::factory()->create([
        'fecha_contratacion' => '2020-01-01',
    ]);

    // 2. Crear dos gestiones
    $gestion2022 = Gestion::create(['anio' => 2022]);
    $gestion2023 = Gestion::create(['anio' => 2023]);

    // 3. Asignar días de vacación (20 para 2022, 30 para 2023)
    Vacacion::create([
        'empleado_id' => $empleado->id,
        'gestion_id' => $gestion2022->id,
        'dias_disponibles' => 20,
    ]);

    Vacacion::create([
        'empleado_id' => $empleado->id,
        'gestion_id' => $gestion2023->id,
        'dias_disponibles' => 30,
    ]);

    // 4. Ejecutar el servicio para solicitar 25 días
    $service = new VacacionService();
    $service->registrarSolicitud([
        'empleado_id' => $empleado->id,
        'fecha_inicio' => '2026-04-01',
        'fecha_fin' => '2026-04-25',
        'dias_solicitados' => 25,
        'motivo' => 'Vacaciones familiares',
    ]);

    // 5. Verificar resultados
    // Gestión 2022: Tenía 20, se usaron todos -> Quedan 0
    $v2022 = Vacacion::where('empleado_id', $empleado->id)
        ->where('gestion_id', $gestion2022->id)
        ->first();
    expect((float)$v2022->dias_disponibles)->toBe(0.0);

    // Gestión 2023: Tenía 30, se usaron 5 (25 total - 20 de 2022) -> Quedan 25
    $v2023 = Vacacion::where('empleado_id', $empleado->id)
        ->where('gestion_id', $gestion2023->id)
        ->first();
    expect((float)$v2023->dias_disponibles)->toBe(25.0);

    // Total disponible debería ser 25
    expect($service->obtenerTotalDiasDisponibles($empleado->id))->toBe(25.0);
});
