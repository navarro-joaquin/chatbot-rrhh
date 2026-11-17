<?php

use App\Livewire\SaldoVacacionTable;
use App\Models\Empleado;
use App\Models\Gestion;
use App\Models\User;
use App\Models\Vacacion;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->gestion2025 = Gestion::create(['anio' => 2025]);
    $this->gestion2026 = Gestion::create(['anio' => 2026]);
});

/**
 * @param  array<int, float>  $saldos  Saldo por gestion_id.
 */
function crearEmpleadoConSaldos(string $nombre, array $saldos, bool $activo = true): Empleado
{
    $empleado = Empleado::factory()->create(['nombre_completo' => $nombre, 'estado' => $activo]);

    foreach ($saldos as $gestionId => $dias) {
        Vacacion::create(['empleado_id' => $empleado->id, 'gestion_id' => $gestionId, 'dias_disponibles' => $dias]);
    }

    return $empleado;
}

it('lista el saldo acumulado de cada empleado del mayor al menor', function () {
    crearEmpleadoConSaldos('Saldo Medio', [$this->gestion2026->id => 10]);
    crearEmpleadoConSaldos('Saldo Mayor', [$this->gestion2025->id => 15, $this->gestion2026->id => 15.5]);
    crearEmpleadoConSaldos('Saldo Menor', [$this->gestion2026->id => -2], activo: false);
    Empleado::factory()->create(['nombre_completo' => 'Sin Vacaciones']);

    Livewire::test(SaldoVacacionTable::class)
        ->assertSeeInOrder([
            'Saldo Mayor', 'Activo', '2', '30 días y 4 horas',
            'Saldo Medio', '10 días',
            'Saldo Menor', 'Inactivo', '-2 días',
        ])
        ->assertDontSee('Sin Vacaciones');
});

it('ordena el saldo acumulado de menor a mayor', function () {
    crearEmpleadoConSaldos('Saldo Mayor', [$this->gestion2026->id => 20]);
    crearEmpleadoConSaldos('Saldo Menor', [$this->gestion2026->id => 1]);

    Livewire::test(SaldoVacacionTable::class)
        ->call('sortBy', 'saldo_total')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Saldo Menor', 'Saldo Mayor']);
});

it('no lista empleados eliminados', function () {
    crearEmpleadoConSaldos('Empleado Eliminado', [$this->gestion2026->id => 30])->delete();

    Livewire::test(SaldoVacacionTable::class)
        ->assertDontSee('Empleado Eliminado');
});

it('busca saldos por nombre de empleado', function () {
    crearEmpleadoConSaldos('Ana Busqueda', [$this->gestion2026->id => 5]);
    crearEmpleadoConSaldos('Luis Otro', [$this->gestion2026->id => 6]);

    Livewire::test(SaldoVacacionTable::class)
        ->set('search', 'ana')
        ->assertSee('Ana Busqueda')
        ->assertDontSee('Luis Otro');
});

it('el enlace ver saldos del dashboard abre el reporte de saldos de vacaciones', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('reportes.saldos-vacaciones'), false);

    $this->get(route('reportes.saldos-vacaciones'))
        ->assertOk()
        ->assertSee('Saldos de vacaciones');
});
