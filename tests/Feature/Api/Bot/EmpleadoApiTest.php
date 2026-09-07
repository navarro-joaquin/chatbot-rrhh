<?php

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Gestion;
use App\Models\SolicitudCompensacion;
use App\Models\SolicitudVacacion;
use App\Models\User;
use App\Models\Vacacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->empleado = Empleado::create([
        'nombre_completo' => 'Empleado Bot',
        'carnet_identidad' => 'BOT-1001',
        'telefono' => '70001001',
        'correo_electronico' => 'bot@example.com',
        'estado' => true,
    ]);

    $this->contrato = EmpleadoContrato::create([
        'empleado_id' => $this->empleado->id,
        'tipo' => 'Indefinido',
        'nro_item' => 'ITEM-BOT',
        'fecha_inicio' => '2025-01-01',
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);
});

it('requiere autenticacion para consultar la api del bot', function () {
    $this->getJson('/api/v1/bot/empleados/por-telefono/70001001')
        ->assertUnauthorized();
});

it('requiere la capacidad bot read', function () {
    Sanctum::actingAs(User::factory()->create(), ['otra:capacidad']);

    $this->getJson('/api/v1/bot/empleados/por-telefono/70001001')
        ->assertForbidden();
});

it('crea un token limitado para el bot', function () {
    $user = User::factory()->create();

    $this->artisan('app:crear-token-bot', ['email' => $user->email])
        ->assertSuccessful();

    expect($user->tokens()->sole()->abilities)->toBe(['bot:read']);
});

it('busca un empleado activo usando un numero de whatsapp', function () {
    Sanctum::actingAs(User::factory()->create(), ['bot:read']);

    $this->getJson('/api/v1/bot/empleados/por-telefono/59170001001@s.whatsapp.net')
        ->assertOk()
        ->assertJsonPath('data.id', $this->empleado->id)
        ->assertJsonPath('data.nombre_completo', 'Empleado Bot')
        ->assertJsonPath('data.contrato_vigente.nro_item', 'ITEM-BOT');
});

it('devuelve los saldos disponibles del empleado', function () {
    Sanctum::actingAs(User::factory()->create(), ['bot:read']);
    $gestion = Gestion::create(['anio' => 2026]);

    Vacacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $gestion->id,
        'dias_disponibles' => 12.5,
    ]);

    Compensacion::create([
        'empleado_id' => $this->empleado->id,
        'gestion_id' => $gestion->id,
        'contrato_id' => $this->contrato->id,
        'cantidad_horas' => 4.5,
        'descripcion' => 'Saldo disponible',
        'fecha_registro' => '2026-08-01',
        'estado' => 'disponible',
    ]);

    $this->getJson("/api/v1/bot/empleados/{$this->empleado->id}/vacaciones")
        ->assertOk()
        ->assertJsonPath('meta.total_dias_disponibles', 12.5);

    $this->getJson("/api/v1/bot/empleados/{$this->empleado->id}/compensaciones")
        ->assertOk()
        ->assertJsonPath('meta.total_horas_disponibles', 4.5);
});

it('devuelve las solicitudes del empleado', function () {
    Sanctum::actingAs(User::factory()->create(), ['bot:read']);

    SolicitudVacacion::create([
        'empleado_id' => $this->empleado->id,
        'fecha_inicio' => '2026-09-10',
        'fecha_fin' => '2026-09-12',
        'dias_solicitados' => 2,
        'estado' => 'aprobado',
    ]);

    SolicitudCompensacion::create([
        'empleado_id' => $this->empleado->id,
        'fecha_compensacion' => '2026-09-15',
        'horas_solicitadas' => 3,
        'estado' => 'aprobado',
    ]);

    $this->getJson("/api/v1/bot/empleados/{$this->empleado->id}/solicitudes-vacaciones")
        ->assertOk()
        ->assertJsonPath('data.0.dias_solicitados', 2);

    $this->getJson("/api/v1/bot/empleados/{$this->empleado->id}/solicitudes-compensaciones")
        ->assertOk()
        ->assertJsonPath('data.0.horas_solicitadas', 3);
});
