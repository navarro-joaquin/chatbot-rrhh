<?php

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Gestion;
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
        'contrato_id' => $contrato->id,
        'cantidad_horas' => 8,
        'estado' => 'disponible',
    ]);

    return $contrato;
}

it('finaliza los contratos a plazo fijo vencidos y vence sus compensaciones', function () {
    $vencido = crearContratoConCompensacion('Plazo Fijo', '2026-06-30', $this->gestion->id);

    $this->artisan('app:procesar-fin-contrato')->assertSuccessful();

    $vencido->refresh();

    expect($vencido->estado)->toBe('Finalizado')
        ->and($vencido->es_vigente)->toBeFalse()
        ->and($vencido->compensaciones()->first()->estado)->toBe('vencido');
});

it('no toca contratos vigentes a futuro ni contratos indefinidos', function () {
    $futuro = crearContratoConCompensacion('Plazo Fijo', '2026-07-01', $this->gestion->id);
    $indefinido = crearContratoConCompensacion('Indefinido', null, $this->gestion->id);

    $this->artisan('app:procesar-fin-contrato')->assertSuccessful();

    foreach ([$futuro, $indefinido] as $contrato) {
        $contrato->refresh();

        expect($contrato->estado)->toBe('Vigente')
            ->and($contrato->es_vigente)->toBeTrue()
            ->and($contrato->compensaciones()->first()->estado)->toBe('disponible');
    }
});

it('procesa la fecha indicada con la opcion fecha', function () {
    $contrato = crearContratoConCompensacion('Plazo Fijo', '2026-07-15', $this->gestion->id);

    $this->artisan('app:procesar-fin-contrato', ['--fecha' => '2026-07-15'])->assertSuccessful();

    expect($contrato->fresh()->estado)->toBe('Finalizado');
});
