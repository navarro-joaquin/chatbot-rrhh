<?php

use App\Console\Commands\ProcesarVacacionesAnuales;
use App\Models\Antiguedad;
use App\Models\ConsolidacionVacacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Gestion;
use App\Models\Vacacion;
use App\Services\VacacionService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Antiguedad::query()->create([
        'anios_desde' => 1,
        'anios_hasta' => 4,
        'dias_asignados' => 15,
    ]);

    $this->empleado = Empleado::create([
        'nombre_completo' => 'Empleado Cron',
        'carnet_identidad' => 'CI-CRON',
        'telefono' => '70009001',
        'correo_electronico' => 'cron@test.com',
        'estado' => true,
    ]);

    EmpleadoContrato::create([
        'empleado_id' => $this->empleado->id,
        'tipo' => 'Indefinido',
        'nro_item' => 'ITEM-CRON',
        'fecha_inicio' => '2023-01-03',
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('no duplica la consolidacion al reprocesar la misma fecha despues de un consumo', function () {
    $service = app(VacacionService::class);

    $service->procesarVacacionesAutomaticas(Carbon::parse('2024-01-03'));

    $service->registrarSolicitud([
        'empleado_id' => $this->empleado->id,
        'fecha_inicio' => '2024-02-05',
        'fecha_fin' => '2024-02-09',
        'dias_solicitados' => 5,
    ]);

    $reproceso = $service->procesarVacacionesAutomaticas(Carbon::parse('2024-01-03'));

    expect($reproceso)->toHaveCount(0)
        ->and($service->obtenerTotalDiasDisponibles($this->empleado->id))->toBe(10.0)
        ->and(ConsolidacionVacacion::where('empleado_id', $this->empleado->id)->count())->toBe(1);
});

it('recupera los aniversarios de los dias en que el proceso no se ejecuto', function () {
    Carbon::setTestNow('2024-01-05 00:00:00');
    Cache::forever(ProcesarVacacionesAnuales::CACHE_ULTIMA_FECHA, '2024-01-01');

    $this->artisan('app:procesar-vacaciones-anuales')->assertSuccessful();

    $service = app(VacacionService::class);

    expect($service->obtenerTotalDiasDisponibles($this->empleado->id))->toBe(15.0)
        ->and(Cache::get(ProcesarVacacionesAnuales::CACHE_ULTIMA_FECHA))->toBe('2024-01-05');

    $this->artisan('app:procesar-vacaciones-anuales')->assertSuccessful();

    expect($service->obtenerTotalDiasDisponibles($this->empleado->id))->toBe(15.0);
});

it('procesa solo el dia actual cuando no hay una ejecucion previa registrada', function () {
    Carbon::setTestNow('2024-01-05 00:00:00');

    $this->artisan('app:procesar-vacaciones-anuales')->assertSuccessful();

    expect(Vacacion::where('empleado_id', $this->empleado->id)->count())->toBe(0)
        ->and(Cache::get(ProcesarVacacionesAnuales::CACHE_ULTIMA_FECHA))->toBe('2024-01-05');
});

it('procesa solo la fecha indicada sin mover la ultima fecha procesada', function () {
    Carbon::setTestNow('2024-06-01 00:00:00');
    Cache::forever(ProcesarVacacionesAnuales::CACHE_ULTIMA_FECHA, '2024-05-31');

    $this->artisan('app:procesar-vacaciones-anuales', ['--fecha' => '2024-01-03'])->assertSuccessful();

    expect(app(VacacionService::class)->obtenerTotalDiasDisponibles($this->empleado->id))->toBe(15.0)
        ->and(Cache::get(ProcesarVacacionesAnuales::CACHE_ULTIMA_FECHA))->toBe('2024-05-31');
});

it('impide registrar dos vacaciones del mismo empleado en la misma gestion', function () {
    $gestion = Gestion::create(['anio' => 2024]);

    Vacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $gestion->id,
        'dias_disponibles' => 15,
    ]);

    expect(fn () => Vacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $gestion->id,
        'dias_disponibles' => 5,
    ]))->toThrow(QueryException::class);
});
