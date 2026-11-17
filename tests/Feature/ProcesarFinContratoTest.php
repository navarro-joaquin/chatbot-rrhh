<?php

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Gestion;
use App\Services\CompensacionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-06-30 00:00:00');

    $this->gestion = Gestion::create(['anio' => 2026]);
});

afterEach(function () {
    Carbon::setTestNow();
});

function crearContratoConCompensacion(string $tipo, ?string $fechaFin, int $gestionId): EmpleadoContrato
{
    $contrato = EmpleadoContrato::create([
        'empleado_id' => Empleado::factory()->create()->id,
        'tipo' => $tipo,
        'numero_contrato' => $tipo === 'Plazo Fijo' ? 'PF-'.fake()->unique()->numerify('####') : null,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => $fechaFin,
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);

    Compensacion::create([
        'empleado_id' => $contrato->empleado_id,
        'gestion_id' => $gestionId,
        'cantidad_horas' => 8,
        'estado' => 'disponible',
    ]);

    return $contrato;
}

it('finaliza los contratos a plazo fijo vencidos sin vencer las compensaciones del empleado', function () {
    $vencido = crearContratoConCompensacion('Plazo Fijo', '2026-06-30', $this->gestion->id);

    $this->artisan('app:procesar-fin-contrato')->assertSuccessful();

    $vencido->refresh();

    expect($vencido->estado)->toBe('Finalizado')
        ->and($vencido->es_vigente)->toBeFalse()
        ->and($vencido->empleado->compensaciones()->first()->estado)->toBe('disponible');
});

it('no toca contratos vigentes a futuro ni contratos indefinidos', function () {
    $futuro = crearContratoConCompensacion('Plazo Fijo', '2026-07-01', $this->gestion->id);
    $indefinido = crearContratoConCompensacion('Indefinido', null, $this->gestion->id);

    $this->artisan('app:procesar-fin-contrato')->assertSuccessful();

    foreach ([$futuro, $indefinido] as $contrato) {
        $contrato->refresh();

        expect($contrato->estado)->toBe('Vigente')
            ->and($contrato->es_vigente)->toBeTrue()
            ->and($contrato->empleado->compensaciones()->first()->estado)->toBe('disponible');
    }
});

it('conserva el saldo de compensaciones con el nuevo contrato de la siguiente gestion', function () {
    $anterior = crearContratoConCompensacion('Plazo Fijo', '2026-06-30', $this->gestion->id);
    $empleado = $anterior->empleado;

    $this->artisan('app:procesar-fin-contrato')->assertSuccessful();

    $gestionSiguiente = Gestion::create(['anio' => 2027]);
    EmpleadoContrato::create([
        'empleado_id' => $empleado->id,
        'tipo' => 'Plazo Fijo',
        'fecha_inicio' => '2027-01-02',
        'fecha_fin' => '2027-12-31',
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);
    Compensacion::create([
        'empleado_id' => $empleado->id,
        'gestion_id' => $gestionSiguiente->id,
        'cantidad_horas' => 2,
        'estado' => 'disponible',
    ]);

    $servicio = app(CompensacionService::class);

    expect($servicio->obtenerTotalHorasDisponibles($empleado->id))->toBe(10.0);

    $servicio->registrarSolicitud([
        'empleado_id' => $empleado->id,
        'fecha_compensacion' => '2027-02-01',
        'horas_solicitadas' => 9,
    ]);

    expect($servicio->obtenerTotalHorasDisponibles($empleado->id))->toBe(1.0)
        ->and(Compensacion::where('gestion_id', $this->gestion->id)->value('estado'))->toBe('utilizado');
});

it('procesa la fecha indicada con la opcion fecha', function () {
    $contrato = crearContratoConCompensacion('Plazo Fijo', '2026-07-15', $this->gestion->id);

    $this->artisan('app:procesar-fin-contrato', ['--fecha' => '2026-07-15'])->assertSuccessful();

    expect($contrato->fresh()->estado)->toBe('Finalizado');
});
