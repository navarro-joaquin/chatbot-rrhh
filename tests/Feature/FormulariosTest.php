<?php

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Feriado;
use App\Models\Gestion;
use App\Models\SolicitudCompensacion;
use App\Models\SolicitudVacacion;
use App\Models\User;
use App\Models\Vacacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->empleado = Empleado::factory()->create();
    $this->gestion = Gestion::create(['anio' => 2026]);
    $this->contrato = EmpleadoContrato::create([
        'empleado_id' => $this->empleado->id,
        'tipo' => 'Indefinido',
        'fecha_inicio' => '2023-01-03',
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);
});

it('registra una gestion y rechaza un anio repetido', function () {
    Livewire::test('gestiones')
        ->call('create')
        ->set('form.anio', 2027)
        ->call('save')
        ->assertHasNoErrors();

    expect(Gestion::where('anio', 2027)->exists())->toBeTrue();

    Livewire::test('gestiones')
        ->call('create')
        ->set('form.anio', 2027)
        ->call('save')
        ->assertHasErrors('form.anio');
});

it('registra un feriado y exige sus datos obligatorios', function () {
    Livewire::test('feriados')
        ->call('create')
        ->call('save')
        ->assertHasErrors(['form.nombre', 'form.fecha', 'form.gestion_id']);

    Livewire::test('feriados')
        ->call('create')
        ->set('form.nombre', 'Dia del Trabajo')
        ->set('form.fecha', '2026-05-01')
        ->set('form.gestion_id', $this->gestion->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Feriado::where('nombre', 'Dia del Trabajo')->exists())->toBeTrue();
});

it('registra una vacacion en dias, horas y minutos y evita duplicar la gestion', function () {
    Livewire::test('vacaciones')
        ->call('create')
        ->set('form.empleado_id', $this->empleado->id)
        ->set('form.gestion_id', $this->gestion->id)
        ->set('form.dias_disponibles_dias', 10)
        ->set('form.dias_disponibles_horas', 4)
        ->set('form.dias_disponibles_minutos', 0)
        ->call('save')
        ->assertHasNoErrors();

    expect((float) Vacacion::firstWhere('empleado_id', $this->empleado->id)->dias_disponibles)->toBe(10.5);

    Livewire::test('vacaciones')
        ->call('create')
        ->set('form.empleado_id', $this->empleado->id)
        ->set('form.gestion_id', $this->gestion->id)
        ->set('form.dias_disponibles_dias', 5)
        ->call('save')
        ->assertHasErrors('form.gestion_id');

    expect(Vacacion::where('empleado_id', $this->empleado->id)->count())->toBe(1);
});

it('registra una solicitud de vacaciones y descuenta el saldo', function () {
    Vacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $this->gestion->id,
        'dias_disponibles' => 10,
    ]);

    Livewire::test('solicitudes-vacaciones')
        ->call('create')
        ->set('form.empleado_id', $this->empleado->id)
        ->set('form.fecha_inicio', '2026-04-06')
        ->set('form.fecha_fin', '2026-04-08')
        ->set('form.dias_solicitados_dias', 3)
        ->call('save')
        ->assertHasNoErrors();

    expect(SolicitudVacacion::count())->toBe(1)
        ->and((float) Vacacion::first()->dias_disponibles)->toBe(7.0);
});

it('rechaza una solicitud de vacaciones mayor al saldo disponible', function () {
    Vacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $this->gestion->id,
        'dias_disponibles' => 2,
    ]);

    Livewire::test('solicitudes-vacaciones')
        ->call('create')
        ->set('form.empleado_id', $this->empleado->id)
        ->set('form.fecha_inicio', '2026-04-06')
        ->set('form.fecha_fin', '2026-04-10')
        ->set('form.dias_solicitados_dias', 5)
        ->call('save')
        ->assertHasErrors('form.dias_solicitados');

    expect(SolicitudVacacion::count())->toBe(0)
        ->and((float) Vacacion::first()->dias_disponibles)->toBe(2.0);
});

it('registra una compensacion asociada al contrato vigente', function () {
    Livewire::test('compensaciones')
        ->call('create')
        ->set('form.empleado_id', $this->empleado->id)
        ->set('form.gestion_id', $this->gestion->id)
        ->set('form.cantidad_horas_horas', 6)
        ->set('form.cantidad_horas_minutos', 30)
        ->set('form.fecha_registro', '2026-03-10')
        ->set('form.estado', 'disponible')
        ->call('save')
        ->assertHasNoErrors();

    $compensacion = Compensacion::firstWhere('empleado_id', $this->empleado->id);

    expect((float) $compensacion->cantidad_horas)->toBe(6.5)
        ->and($compensacion->contrato_id)->toBe($this->contrato->id);
});

it('registra una solicitud de compensacion y rechaza la que supera el saldo', function () {
    Compensacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $this->gestion->id,
        'contrato_id' => $this->contrato->id,
        'cantidad_horas' => 6,
        'fecha_registro' => '2026-03-10',
        'estado' => 'disponible',
    ]);

    Livewire::test('solicitudes-compensaciones')
        ->call('create')
        ->set('form.empleado_id', $this->empleado->id)
        ->set('form.fecha_compensacion', '2026-04-06')
        ->set('form.horas_solicitadas_horas', 4)
        ->set('form.horas_solicitadas_minutos', 0)
        ->call('save')
        ->assertHasNoErrors();

    expect(SolicitudCompensacion::count())->toBe(1)
        ->and((float) Compensacion::first()->cantidad_horas)->toBe(2.0);

    Livewire::test('solicitudes-compensaciones')
        ->call('create')
        ->set('form.empleado_id', $this->empleado->id)
        ->set('form.fecha_compensacion', '2026-04-07')
        ->set('form.horas_solicitadas_horas', 5)
        ->set('form.horas_solicitadas_minutos', 0)
        ->call('save')
        ->assertHasErrors('form.horas_solicitadas');

    expect(SolicitudCompensacion::count())->toBe(1);
});

it('al registrar un nuevo contrato vigente deja de estar vigente el anterior', function () {
    Livewire::test('empleado-detalle', ['id' => $this->empleado->id])
        ->call('createContrato')
        ->set('contratoForm.tipo', 'Plazo Fijo')
        ->set('contratoForm.numero_contrato', 'PF-001')
        ->set('contratoForm.fecha_inicio', '2026-01-01')
        ->set('contratoForm.fecha_fin', '2026-12-31')
        ->set('contratoForm.es_vigente', true)
        ->call('saveContrato')
        ->assertHasNoErrors();

    $vigentes = EmpleadoContrato::where('empleado_id', $this->empleado->id)->where('es_vigente', true)->get();

    expect($vigentes)->toHaveCount(1)
        ->and($vigentes->first()->numero_contrato)->toBe('PF-001')
        ->and($this->contrato->fresh()->es_vigente)->toBeFalse();
});

it('permite registrar un contrato a plazo fijo sin numero de contrato', function () {
    Livewire::test('empleado-detalle', ['id' => $this->empleado->id])
        ->call('createContrato')
        ->set('contratoForm.tipo', 'Plazo Fijo')
        ->set('contratoForm.fecha_inicio', '2026-01-01')
        ->set('contratoForm.fecha_fin', '2026-12-31')
        ->call('saveContrato')
        ->assertHasNoErrors();

    $nuevo = EmpleadoContrato::where('empleado_id', $this->empleado->id)->where('es_vigente', true)->first();

    expect($nuevo->tipo)->toBe('Plazo Fijo')
        ->and($nuevo->numero_contrato)->toBeNull()
        ->and($this->contrato->fresh()->es_vigente)->toBeFalse();
});
