<?php

use App\Livewire\ConsolidacionMensualTable;
use App\Models\Antiguedad;
use App\Models\ConsolidacionVacacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Gestion;
use App\Models\User;
use App\Services\VacacionService;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-11-17 10:00:00');

    $this->actingAs(User::factory()->create());

    Gestion::create(['anio' => 2025]);
    Gestion::create(['anio' => 2026]);

    Antiguedad::create(['anios_desde' => 1, 'anios_hasta' => 5, 'dias_asignados' => 15]);
    Antiguedad::create(['anios_desde' => 6, 'anios_hasta' => 10, 'dias_asignados' => 20]);
    Antiguedad::create(['anios_desde' => 11, 'anios_hasta' => 99, 'dias_asignados' => 30]);
});

afterEach(function () {
    Carbon::setTestNow();
});

function empleadoConContrato(string $nombre, string $inicio, string $tipo = 'Indefinido', bool $activo = true): Empleado
{
    $empleado = Empleado::factory()->create(['nombre_completo' => $nombre, 'estado' => $activo]);

    EmpleadoContrato::create([
        'empleado_id' => $empleado->id,
        'tipo' => $tipo,
        'fecha_inicio' => $inicio,
        'fecha_fin' => $tipo === 'Indefinido' ? null : '2026-12-31',
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);

    return $empleado;
}

it('calcula aniversario, años de servicio y dias segun la fecha de inicio del contrato', function () {
    empleadoConContrato('Ana Marzo', '2020-03-15');
    empleadoConContrato('Luis Marzo', '2010-03-02');
    empleadoConContrato('Eva Julio', '2018-07-10');
    empleadoConContrato('Bisiesto', '2016-02-29');
    empleadoConContrato('Primer Anio', '2026-05-01');
    empleadoConContrato('Plazo Fijo', '2015-03-01', tipo: 'Plazo Fijo');
    empleadoConContrato('Inactivo', '2015-03-01', activo: false);

    $filas = app(VacacionService::class)->consolidacionesPorContrato(2026)->keyBy('empleado');

    expect($filas->keys()->all())->toBe(['Bisiesto', 'Luis Marzo', 'Ana Marzo', 'Eva Julio'])
        ->and($filas['Ana Marzo'])->toMatchArray(['aniversario' => '2026-03-15', 'mes' => 3, 'anios' => 6, 'dias' => 20.0])
        ->and($filas['Luis Marzo'])->toMatchArray(['aniversario' => '2026-03-02', 'anios' => 16, 'dias' => 30.0])
        ->and($filas['Eva Julio'])->toMatchArray(['aniversario' => '2026-07-10', 'anios' => 8, 'dias' => 20.0])
        ->and($filas['Bisiesto'])->toMatchArray(['aniversario' => '2026-02-28', 'mes' => 2, 'anios' => 9]);
});

it('agrupa por mes del aniversario del contrato sin usar los registros de consolidacion', function () {
    $ana = empleadoConContrato('Ana Marzo', '2020-03-15');
    empleadoConContrato('Luis Marzo', '2010-03-02');
    empleadoConContrato('Eva Julio', '2018-07-10');

    // Un registro de consolidación en otro mes no debe influir en el reporte.
    ConsolidacionVacacion::create([
        'empleado_id' => $ana->id,
        'gestion_id' => Gestion::where('anio', 2026)->value('id'),
        'dias_anadidos' => 99,
        'dias_totales_despues' => 99,
        'origen' => 'contrato',
        'accion' => 'creada',
    ]);

    $componente = Livewire::test('reporte-consolidaciones')->assertSet('anio', 2026);
    $meses = $componente->viewData('meses')->keyBy('numero');

    expect($meses[3])->toMatchArray(['nombre' => 'Marzo', 'empleados' => 2, 'dias' => 50.0])
        ->and($meses[7])->toMatchArray(['nombre' => 'Julio', 'empleados' => 1, 'dias' => 20.0])
        ->and($meses[11]['empleados'])->toBe(0);

    $componente->assertViewHas('totalEmpleados', 3)
        ->assertViewHas('totalDias', 70.0)
        ->assertViewHas('anios', [2026, 2025]);
});

it('recalcula los años de servicio al cambiar de año', function () {
    empleadoConContrato('Ana Marzo', '2020-03-15');

    $componente = Livewire::test('reporte-consolidaciones')->set('anio', 2025);

    expect($componente->viewData('meses')->firstWhere('numero', 3)['dias'])->toBe(15.0);
});

it('selecciona un mes para ver su detalle y lo deselecciona al volver a elegirlo', function () {
    Livewire::test('reporte-consolidaciones')
        ->call('seleccionarMes', 3)
        ->assertSet('mes', 3)
        ->assertSee('Detalle de Marzo 2026')
        ->call('seleccionarMes', 3)
        ->assertSet('mes', null)
        ->call('seleccionarMes', 3)
        ->set('anio', 2025)
        ->assertSet('mes', null);
});

it('el detalle muestra solo los aniversarios del mes elegido', function () {
    empleadoConContrato('Ana Marzo', '2020-03-15');
    empleadoConContrato('Eva Julio', '2018-07-10');

    Livewire::test(ConsolidacionMensualTable::class, ['anio' => 2026, 'mes' => 3])
        ->assertSee('Ana Marzo')
        ->assertSee('15/03/2026')
        ->assertSee('20 días')
        ->assertSee('Cumplido')
        ->assertDontSee('Eva Julio');

    Livewire::test(ConsolidacionMensualTable::class, ['anio' => 2026])
        ->assertSeeInOrder(['Ana Marzo', 'Eva Julio']);
});

it('marca como proximo el aniversario que aun no llega', function () {
    empleadoConContrato('Dario Diciembre', '2015-12-20');

    Livewire::test(ConsolidacionMensualTable::class, ['anio' => 2026, 'mes' => 12])
        ->assertSee('Dario Diciembre')
        ->assertSee('Próximo');
});
