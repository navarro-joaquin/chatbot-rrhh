<?php

use App\Livewire\ContratoPorVencerTable;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-11-17 10:00:00');

    $this->actingAs(User::factory()->create());
});

afterEach(function () {
    Carbon::setTestNow();
});

function crearContratoPorVencer(string $nombre, ?string $fechaFin, bool $vigente = true): EmpleadoContrato
{
    return EmpleadoContrato::create([
        'empleado_id' => Empleado::factory()->create(['nombre_completo' => $nombre])->id,
        'tipo' => $fechaFin ? 'Plazo Fijo' : 'Indefinido',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => $fechaFin,
        'estado' => $vigente ? 'Vigente' : 'Finalizado',
        'es_vigente' => $vigente,
    ]);
}

it('lista solo los contratos vigentes que vencen en los proximos 60 dias, del mas proximo al mas lejano', function () {
    crearContratoPorVencer('Lejano Dentro', '2027-01-16');
    crearContratoPorVencer('Hoy Vence', '2026-11-17');
    crearContratoPorVencer('Pronto Vence', '2026-11-18');
    crearContratoPorVencer('Fuera Rango', '2027-01-17');
    crearContratoPorVencer('Ya Vencido', '2026-11-16');
    crearContratoPorVencer('Sin Fecha', null);
    crearContratoPorVencer('No Vigente', '2026-12-01', vigente: false);

    Livewire::test(ContratoPorVencerTable::class)
        ->assertSeeInOrder(['Hoy Vence', 'Vence hoy', 'Pronto Vence', '1 día', 'Lejano Dentro', '60 días'])
        ->assertDontSee('Fuera Rango')
        ->assertDontSee('Ya Vencido')
        ->assertDontSee('Sin Fecha')
        ->assertDontSee('No Vigente');
});

it('no lista contratos de empleados eliminados', function () {
    $contrato = crearContratoPorVencer('Empleado Eliminado', '2026-12-01');
    $contrato->empleado->delete();

    Livewire::test(ContratoPorVencerTable::class)
        ->assertDontSee('Empleado Eliminado');
});

it('busca contratos por vencer por nombre de empleado', function () {
    crearContratoPorVencer('Ana Busqueda', '2026-12-01');
    crearContratoPorVencer('Luis Otro', '2026-12-02');

    Livewire::test(ContratoPorVencerTable::class)
        ->set('search', 'ana')
        ->assertSee('Ana Busqueda')
        ->assertDontSee('Luis Otro');
});

it('el boton revisar del dashboard abre el reporte de contratos por vencer', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('reportes.contratos-por-vencer'), false);

    $this->get(route('reportes.contratos-por-vencer'))
        ->assertOk()
        ->assertSee('Contratos por vencer');
});
