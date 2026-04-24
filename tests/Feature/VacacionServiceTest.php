<?php

use App\Models\Antiguedad;
use App\Models\Empleado;
use App\Models\EmpleadoAntiguedad;
use App\Models\EmpleadoContrato;
use App\Models\Feriado;
use App\Models\Gestion;
use App\Models\Vacacion;
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

    Antiguedad::query()->create([
        'anios_desde' => 10,
        'anios_hasta' => 99,
        'dias_asignados' => 30,
    ]);
});

it('descuenta dias de vacacion usando el metodo FIFO', function () {
    $empleado = crearEmpleado('FIFO Test', '1001');

    $gestion2022 = Gestion::create(['anio' => 2022]);
    $gestion2023 = Gestion::create(['anio' => 2023]);

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

    $service = app(VacacionService::class);

    $service->registrarSolicitud([
        'empleado_id' => $empleado->id,
        'fecha_inicio' => '2026-04-01',
        'fecha_fin' => '2026-04-25',
        'dias_solicitados' => 25,
        'motivo' => 'Vacaciones familiares',
    ]);

    $v2022 = Vacacion::where('empleado_id', $empleado->id)
        ->where('gestion_id', $gestion2022->id)
        ->first();

    $v2023 = Vacacion::where('empleado_id', $empleado->id)
        ->where('gestion_id', $gestion2023->id)
        ->first();

    expect((float) $v2022->dias_disponibles)->toBe(0.0)
        ->and((float) $v2023->dias_disponibles)->toBe(25.0)
        ->and($service->obtenerTotalDiasDisponibles($empleado->id))->toBe(25.0);
});

it('calcula dias solicitados excluyendo feriados activos entre semana', function () {
    $gestion2026 = Gestion::create(['anio' => 2026]);

    Feriado::create([
        'nombre' => 'Dia del Trabajo',
        'fecha' => '2026-05-01',
        'gestion_id' => $gestion2026->id,
        'estado' => true,
    ]);

    $service = app(VacacionService::class);

    $dias = $service->calcularDiasSolicitados('2026-04-27', '2026-05-04');

    expect($dias)->toBe(5.0);
});

it('vacaciones, ejemplo real 1, Juan Perez', function () {
    $gestion2024 = Gestion::create(['anio' => 2024]);
    $gestion2025 = Gestion::create(['anio' => 2025]);
    $gestion2026 = Gestion::create(['anio' => 2026]);

    $service = app(VacacionService::class);

    $empleado = crearEmpleadoConContratoPlanta(
        nombre: 'Juan Perez',
        sufijo: '550',
        fechaInicio: '2023-01-03'
    );

    $consolidacion1 = $service->procesarVacacionesAutomaticas(Carbon::parse('2024-01-03'));

    registrarAntiguedadReconocida(
        empleado: $empleado,
        fechaReconocida: '2018-02-08',
        fechaReconocimiento: '2024-02-21 09:00:00'
    );

    $consolidacion2 = $service->procesarVacacionesAutomaticas(Carbon::parse('2025-02-08'));
    $consolidacion3 = $service->procesarVacacionesAutomaticas(Carbon::parse('2026-02-08'));

    $v2024 = Vacacion::where('empleado_id', $empleado->id)
        ->where('gestion_id', $gestion2024->id)
        ->first();

    $v2025 = Vacacion::where('empleado_id', $empleado->id)
        ->where('gestion_id', $gestion2025->id)
        ->first();

    $v2026 = Vacacion::where('empleado_id', $empleado->id)
        ->where('gestion_id', $gestion2026->id)
        ->first();

    expect((float) $v2024->dias_disponibles)->toBe(15.0)
        ->and((float) $v2025->dias_disponibles)->toBe(20.0)
        ->and((float) $v2026->dias_disponibles)->toBe(20.0)
        ->and($service->obtenerTotalDiasDisponibles($empleado->id))->toBe(55.0);
});

it('escenario 1 acumula una nueva consolidacion mas beneficiosa dentro de la misma gestion', function () {
    $empleado = crearEmpleadoConContratoPlanta(
        nombre: 'Escenario 1',
        sufijo: '2001',
        fechaInicio: '2023-01-03',
    );

    registrarAntiguedadReconocida(
        empleado: $empleado,
        fechaReconocida: '2019-10-21',
        fechaReconocimiento: '2024-03-05 09:00:00',
    );

    $service = app(VacacionService::class);

    $enero = $service->procesarVacacionesAutomaticas(Carbon::parse('2024-01-03'));
    $octubre = $service->procesarVacacionesAutomaticas(Carbon::parse('2024-10-21'));

    $vacacion2024 = obtenerVacacionPorGestion($empleado->id, 2024);

    expect($enero)->toHaveCount(1)
        ->and($enero[0]['dias'])->toBe(15.0)
        ->and($octubre)->toHaveCount(1)
        ->and($octubre[0]['dias'])->toBe(20.0)
        ->and($octubre[0]['accion'])->toBe('actualizada')
        ->and((float) $vacacion2024->dias_disponibles)->toBe(35.0)
        ->and(Vacacion::where('empleado_id', $empleado->id)->count())->toBe(1);
});

it('acumula la segunda consolidacion aunque exista consumo previo en la misma gestion', function () {
    $empleado = crearEmpleadoConContratoPlanta(
        nombre: 'Escenario 1 con uso',
        sufijo: '2004',
        fechaInicio: '2023-01-03',
    );

    registrarAntiguedadReconocida(
        empleado: $empleado,
        fechaReconocida: '2019-10-21',
        fechaReconocimiento: '2024-03-05 09:00:00',
    );

    $service = app(VacacionService::class);

    $service->procesarVacacionesAutomaticas(Carbon::parse('2024-01-03'));

    $service->registrarSolicitud([
        'empleado_id' => $empleado->id,
        'fecha_inicio' => '2024-04-22',
        'fecha_fin' => '2024-04-26',
        'dias_solicitados' => 5,
        'motivo' => 'Caso de prueba con consumo previo',
    ]);

    $octubre = $service->procesarVacacionesAutomaticas(Carbon::parse('2024-10-21'));

    $vacacion2024 = obtenerVacacionPorGestion($empleado->id, 2024);

    expect($octubre)->toHaveCount(1)
        ->and($octubre[0]['dias'])->toBe(20.0)
        ->and($octubre[0]['accion'])->toBe('actualizada')
        ->and((float) $vacacion2024->dias_disponibles)->toBe(30.0)
        ->and(Vacacion::where('empleado_id', $empleado->id)->count())->toBe(1);
});

it('escenario 2 mantiene consolidaciones en gestiones distintas', function () {
    $empleado = crearEmpleadoConContratoPlanta(
        nombre: 'Escenario 2',
        sufijo: '2002',
        fechaInicio: '2023-01-03',
    );

    registrarAntiguedadReconocida(
        empleado: $empleado,
        fechaReconocida: '2018-02-08',
        fechaReconocimiento: '2024-02-21 09:00:00',
    );

    $service = app(VacacionService::class);

    $enero2024 = $service->procesarVacacionesAutomaticas(Carbon::parse('2024-01-03'));
    $febrero2025 = $service->procesarVacacionesAutomaticas(Carbon::parse('2025-02-08'));

    $vacacion2024 = obtenerVacacionPorGestion($empleado->id, 2024);
    $vacacion2025 = obtenerVacacionPorGestion($empleado->id, 2025);

    expect($enero2024)->toHaveCount(1)
        ->and($enero2024[0]['dias'])->toBe(15.0)
        ->and($febrero2025)->toHaveCount(1)
        ->and($febrero2025[0]['dias'])->toBe(20.0)
        ->and((float) $vacacion2024->dias_disponibles)->toBe(15.0)
        ->and((float) $vacacion2025->dias_disponibles)->toBe(20.0)
        ->and(Vacacion::where('empleado_id', $empleado->id)->count())->toBe(2);
});

it('escenario 3 protege el derecho cuando el reconocimiento desplaza la siguiente consolidacion', function () {
    $empleado = crearEmpleadoConContratoPlanta(
        nombre: 'Escenario 3',
        sufijo: '2003',
        fechaInicio: '2022-11-03',
    );

    registrarAntiguedadReconocida(
        empleado: $empleado,
        fechaReconocida: '2018-01-08',
        fechaReconocimiento: '2024-08-21 09:00:00',
    );

    $service = app(VacacionService::class);

    $noviembre2023 = $service->procesarVacacionesAutomaticas(Carbon::parse('2023-11-03'));
    $noviembre2024 = $service->procesarVacacionesAutomaticas(Carbon::parse('2024-11-03'));

    $vacacion2023 = obtenerVacacionPorGestion($empleado->id, 2023);
    $vacacion2024 = obtenerVacacionPorGestion($empleado->id, 2024);

    expect($noviembre2023)->toHaveCount(1)
        ->and($noviembre2023[0]['dias'])->toBe(15.0)
        ->and($noviembre2024)->toHaveCount(1)
        ->and($noviembre2024[0]['origen'])->toBe('proteccion')
        ->and($noviembre2024[0]['dias'])->toBe(20.0)
        ->and((float) $vacacion2023->dias_disponibles)->toBe(15.0)
        ->and((float) $vacacion2024->dias_disponibles)->toBe(20.0)
        ->and(Vacacion::where('empleado_id', $empleado->id)->count())->toBe(2);
});

function crearEmpleado(string $nombre, string $sufijo): Empleado
{
    return Empleado::create([
        'nombre_completo' => $nombre,
        'carnet_identidad' => 'CI-'.$sufijo,
        'telefono' => '7000'.$sufijo,
        'correo_electronico' => 'empleado'.$sufijo.'@test.com',
        'estado' => true,
    ]);
}

function crearEmpleadoConContratoPlanta(string $nombre, string $sufijo, string $fechaInicio): Empleado
{
    $empleado = crearEmpleado($nombre, $sufijo);

    EmpleadoContrato::create([
        'empleado_id' => $empleado->id,
        'tipo' => 'Planta',
        'numero_contrato' => null,
        'nro_item' => 'ITEM-'.$sufijo,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => null,
        'estado' => 'Vigente',
        'es_vigente' => true,
        'resolucion' => 'RA-'.$sufijo,
    ]);

    return $empleado->fresh();
}

function registrarAntiguedadReconocida(Empleado $empleado, string $fechaReconocida, string $fechaReconocimiento): EmpleadoAntiguedad
{
    $contrato = $empleado->contratoVigente()->firstOrFail();

    $antiguedad = EmpleadoAntiguedad::create([
        'empleado_id' => $empleado->id,
        'contrato_id' => $contrato->id,
        'fecha_reconocida' => $fechaReconocida,
        'vigencia_desde' => Carbon::parse($fechaReconocimiento)->toDateString(),
        'origen' => 'Regularizacion',
        'observaciones' => 'Caso de prueba',
        'vigente' => true,
    ]);

    $antiguedad->forceFill([
        'created_at' => Carbon::parse($fechaReconocimiento),
        'updated_at' => Carbon::parse($fechaReconocimiento),
    ])->save();

    return $antiguedad->fresh();
}

function obtenerVacacionPorGestion(int $empleadoId, int $gestionAnio): ?Vacacion
{
    $gestion = Gestion::firstWhere('anio', $gestionAnio);

    return Vacacion::query()
        ->where('empleado_id', $empleadoId)
        ->where('gestion_id', $gestion?->id)
        ->first();
}
