<?php

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Gestion;
use App\Models\SolicitudCompensacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function crearEmpleadoCompHM(string $sufijo): array
{
    $gestion = Gestion::create(['anio' => 2026]);

    $empleado = Empleado::create([
        'nombre_completo' => 'Empleado Comp HM '.$sufijo,
        'carnet_identidad' => 'CI-COMPHM-'.$sufijo,
        'telefono' => '7100CMPHM'.$sufijo,
        'estado' => true,
    ]);

    $contrato = EmpleadoContrato::create([
        'empleado_id' => $empleado->id,
        'tipo' => 'Indefinido',
        'fecha_inicio' => '2020-01-01',
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);

    return [$empleado, $contrato, $gestion];
}

it('registra una compensacion con horas y minutos', function () {
    [$empleado, $contrato, $gestion] = crearEmpleadoCompHM('01');

    Livewire::test('compensaciones')
        ->call('create')
        ->set('form.empleado_id', $empleado->id)
        ->set('form.gestion_id', $gestion->id)
        ->set('form.cantidad_horas_horas', 5)
        ->set('form.cantidad_horas_minutos', 30)
        ->set('form.fecha_registro', '2026-03-01')
        ->set('form.estado', 'disponible')
        ->call('save')
        ->assertHasNoErrors();

    $compensacion = Compensacion::where('empleado_id', $empleado->id)->first();

    expect($compensacion)->not->toBeNull()
        ->and((float) $compensacion->cantidad_horas)->toBe(5.5)
        ->and($compensacion->contrato_id)->toBe($contrato->id);
});

it('registra una solicitud de 15 minutos', function () {
    [$empleado, $contrato, $gestion] = crearEmpleadoCompHM('02');

    Compensacion::create([
        'empleado_id' => $empleado->id,
        'gestion_id' => $gestion->id,
        'contrato_id' => $contrato->id,
        'cantidad_horas' => 8,
        'fecha_registro' => '2026-03-01',
        'estado' => 'disponible',
    ]);

    Livewire::test('solicitudes-compensaciones')
        ->call('create')
        ->set('form.empleado_id', $empleado->id)
        ->set('form.fecha_compensacion', '2026-09-15')
        ->set('form.horas_solicitadas_horas', 0)
        ->set('form.horas_solicitadas_minutos', 15)
        ->call('save')
        ->assertHasNoErrors();

    $solicitud = SolicitudCompensacion::where('empleado_id', $empleado->id)->first();

    expect($solicitud)->not->toBeNull()
        ->and((float) $solicitud->horas_solicitadas)->toBe(0.25)
        ->and((float) Compensacion::where('empleado_id', $empleado->id)->sum('cantidad_horas'))->toBe(7.75);
});
