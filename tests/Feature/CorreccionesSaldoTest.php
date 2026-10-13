<?php

use App\Exceptions\SaldoInsuficienteException;
use App\Models\Compensacion;
use App\Models\ConsolidacionVacacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Feriado;
use App\Models\Gestion;
use App\Models\SolicitudCompensacion;
use App\Models\SolicitudVacacion;
use App\Models\Vacacion;
use App\Services\CompensacionService;
use App\Services\VacacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->empleado = Empleado::factory()->create();
    $this->gestion = Gestion::create(['anio' => 2026]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('rechaza la solicitud de vacaciones sin guardar nada si el saldo no alcanza', function () {
    Vacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $this->gestion->id,
        'dias_disponibles' => 5,
    ]);

    expect(fn () => app(VacacionService::class)->registrarSolicitud([
        'empleado_id' => $this->empleado->id,
        'fecha_inicio' => '2026-04-01',
        'fecha_fin' => '2026-04-10',
        'dias_solicitados' => 8,
    ]))->toThrow(SaldoInsuficienteException::class);

    expect(SolicitudVacacion::count())->toBe(0)
        ->and(app(VacacionService::class)->obtenerTotalDiasDisponibles($this->empleado->id))->toBe(5.0);
});

it('conserva la solicitud original si al editarla el saldo no alcanza', function () {
    Vacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $this->gestion->id,
        'dias_disponibles' => 5,
    ]);

    $service = app(VacacionService::class);
    $solicitud = $service->registrarSolicitud([
        'empleado_id' => $this->empleado->id,
        'fecha_inicio' => '2026-04-01',
        'fecha_fin' => '2026-04-03',
        'dias_solicitados' => 3,
    ]);

    expect(fn () => $service->actualizarSolicitud($solicitud, [
        'empleado_id' => $this->empleado->id,
        'fecha_inicio' => '2026-04-01',
        'fecha_fin' => '2026-04-10',
        'dias_solicitados' => 8,
    ]))->toThrow(SaldoInsuficienteException::class);

    expect((float) $solicitud->fresh()->dias_solicitados)->toBe(3.0)
        ->and($solicitud->detalles()->count())->toBe(1)
        ->and($service->obtenerTotalDiasDisponibles($this->empleado->id))->toBe(2.0);
});

it('rechaza la solicitud de compensacion sin guardar nada si el saldo no alcanza', function () {
    $contrato = EmpleadoContrato::create([
        'empleado_id' => $this->empleado->id,
        'tipo' => 'Indefinido',
        'fecha_inicio' => '2023-01-03',
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);

    Compensacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $this->gestion->id,
        'contrato_id' => $contrato->id,
        'cantidad_horas' => 4,
        'estado' => 'disponible',
    ]);

    expect(fn () => app(CompensacionService::class)->registrarSolicitud([
        'empleado_id' => $this->empleado->id,
        'fecha_compensacion' => '2026-04-01',
        'horas_solicitadas' => 6,
    ]))->toThrow(SaldoInsuficienteException::class);

    expect(SolicitudCompensacion::count())->toBe(0)
        ->and(app(CompensacionService::class)->obtenerTotalHorasDisponibles($this->empleado->id))->toBe(4.0);
});

it('el saldo del historial coincide con el saldo real del empleado', function () {
    // Saldo cargado manualmente, sin movimiento en el historial.
    Vacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $this->gestion->id,
        'dias_disponibles' => 12.5,
    ]);

    $gestion2027 = Gestion::create(['anio' => 2027]);
    Vacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $gestion2027->id,
        'dias_disponibles' => 15,
    ]);
    ConsolidacionVacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $gestion2027->id,
        'dias_anadidos' => 15,
        'dias_totales_despues' => 15,
        'origen' => 'contrato',
        'accion' => 'creada',
    ]);

    $service = app(VacacionService::class);
    $service->registrarSolicitud([
        'empleado_id' => $this->empleado->id,
        'fecha_inicio' => '2026-04-01',
        'fecha_fin' => '2026-04-03',
        'dias_solicitados' => 3,
    ]);

    // Una solicitud cancelada no debe restar en el historial.
    SolicitudVacacion::create([
        'empleado_id' => $this->empleado->id,
        'fecha_inicio' => '2026-05-01',
        'fecha_fin' => '2026-05-05',
        'dias_solicitados' => 4,
        'estado' => 'cancelado',
    ]);

    $historial = DB::table('v_historial_vacaciones')
        ->where('empleado_id', $this->empleado->id)
        ->get();

    expect((float) $historial->sum('dias'))->toBe($service->obtenerTotalDiasDisponibles($this->empleado->id))
        ->and((float) $historial->sum('dias'))->toBe(24.5)
        ->and((float) $historial->firstWhere('evento', 'Saldo inicial / ajuste manual')->dias)->toBe(12.5)
        ->and($historial->where('evento', 'Solicitud de vacaciones'))->toHaveCount(1);
});

it('no agrega el ajuste manual cuando el historial ya explica todo el saldo', function () {
    Vacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $this->gestion->id,
        'dias_disponibles' => 15,
    ]);
    ConsolidacionVacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $this->gestion->id,
        'dias_anadidos' => 15,
        'dias_totales_despues' => 15,
        'origen' => 'contrato',
        'accion' => 'creada',
    ]);

    expect(DB::table('v_historial_vacaciones')->where('evento', 'Saldo inicial / ajuste manual')->count())->toBe(0);
});

it('cierra la gestion desactivando sus feriados y no falla si la gestion no existe', function () {
    Carbon::setTestNow('2026-12-31 23:59:00');

    $feriado = Feriado::create([
        'nombre' => 'Navidad',
        'fecha' => '2026-12-25',
        'gestion_id' => $this->gestion->id,
        'estado' => true,
    ]);

    $this->artisan('app:procesar-fin-gestion')->assertSuccessful();

    expect($feriado->fresh()->estado)->toBeFalse();

    $this->artisan('app:procesar-fin-gestion', ['--anio' => 2030])
        ->expectsOutputToContain('No existe la gestión 2030')
        ->assertSuccessful();
});

it('sigue descontando los feriados de una gestion cerrada al calcular dias', function () {
    Feriado::create([
        'nombre' => 'Navidad',
        'fecha' => '2026-12-25',
        'gestion_id' => $this->gestion->id,
        'estado' => false,
    ]);

    $service = app(VacacionService::class);

    // Durante la gestión, un feriado desactivado manualmente no cuenta.
    Carbon::setTestNow('2026-12-01');
    expect($service->calcularDiasSolicitados('2026-12-21', '2026-12-28'))->toBe(6.0);

    // Tras el cierre de gestión, el feriado sigue siendo no laborable.
    Carbon::setTestNow('2027-01-10');
    expect($service->calcularDiasSolicitados('2026-12-21', '2026-12-28'))->toBe(5.0);
});
