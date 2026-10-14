<?php

use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Feriado;
use App\Models\Gestion;
use App\Models\SolicitudCompensacion;
use App\Models\SolicitudVacacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function autenticarIntegracion(string $habilidad = 'integracion:read'): void
{
    Sanctum::actingAs(User::factory()->create(), [$habilidad]);
}

it('emite un token de integracion con la credencial correcta', function () {
    config([
        'services.rrhh_integracion.client_secret' => 'secreto',
        'services.rrhh_integracion.user_email' => 'integracion@internal.local',
    ]);
    User::factory()->create(['email' => 'integracion@internal.local']);

    $this->postJson('/api/v1/integracion/auth/token')->assertUnauthorized();

    $this->postJson('/api/v1/integracion/auth/token', [], ['X-Integracion-Client-Secret' => 'secreto'])
        ->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer');
});

it('no acepta la credencial del bot ni tokens del bot', function () {
    config(['services.rrhh_integracion.client_secret' => 'secreto', 'services.rrhh_bot.client_secret' => 'otro']);

    $this->postJson('/api/v1/integracion/auth/token', [], ['X-Bot-Client-Secret' => 'otro'])->assertUnauthorized();

    autenticarIntegracion('bot:read');

    $this->getJson('/api/v1/integracion/empleados')->assertForbidden();
});

it('rechaza peticiones sin token', function () {
    $this->getJson('/api/v1/integracion/empleados')->assertUnauthorized();
});

it('lista empleados con nombre, correo, celular y nro de item del contrato vigente', function () {
    autenticarIntegracion();

    $empleado = Empleado::factory()->create([
        'nombre_completo' => 'Ana Perez',
        'correo_electronico' => 'ana@example.com',
        'telefono' => '70000001',
    ]);
    EmpleadoContrato::create([
        'empleado_id' => $empleado->id,
        'tipo' => 'Indefinido',
        'nro_item' => '145',
        'fecha_inicio' => '2023-01-03',
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);
    EmpleadoContrato::create([
        'empleado_id' => $empleado->id,
        'tipo' => 'Indefinido',
        'nro_item' => '99',
        'fecha_inicio' => '2020-01-03',
        'estado' => 'Finalizado',
        'es_vigente' => false,
    ]);
    Empleado::factory()->create(['estado' => false])->delete();

    $this->getJson('/api/v1/integracion/empleados')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nombre_completo', 'Ana Perez')
        ->assertJsonPath('data.0.correo_electronico', 'ana@example.com')
        ->assertJsonPath('data.0.telefono', '70000001')
        ->assertJsonPath('data.0.nro_item', '145')
        ->assertJsonMissingPath('data.0.carnet_identidad');

    $this->getJson("/api/v1/integracion/empleados/{$empleado->id}")
        ->assertOk()
        ->assertJsonPath('data.nro_item', '145');
});

it('filtra empleados por estado', function () {
    autenticarIntegracion();

    Empleado::factory()->create(['estado' => true]);
    Empleado::factory()->create(['estado' => false]);

    $this->getJson('/api/v1/integracion/empleados?estado=1')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/integracion/empleados?estado=0')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/integracion/empleados')->assertJsonCount(2, 'data');
});

it('lista feriados filtrados por gestion', function () {
    autenticarIntegracion();

    $gestion2025 = Gestion::create(['anio' => 2025]);
    $gestion2026 = Gestion::create(['anio' => 2026]);

    Feriado::create(['nombre' => 'Año Nuevo 2025', 'fecha' => '2025-01-01', 'gestion_id' => $gestion2025->id, 'estado' => true]);
    Feriado::create(['nombre' => 'Dia del Trabajo', 'fecha' => '2026-05-01', 'gestion_id' => $gestion2026->id, 'estado' => true]);

    $this->getJson('/api/v1/integracion/feriados?gestion=2026')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nombre', 'Dia del Trabajo')
        ->assertJsonPath('data.0.gestion', 2026)
        ->assertJsonPath('data.0.fecha', '2026-05-01');

    $this->getJson('/api/v1/integracion/feriados')->assertJsonCount(2, 'data');
});

it('lista solicitudes de vacaciones y compensaciones con filtros', function () {
    autenticarIntegracion();

    $empleado = Empleado::factory()->create(['nombre_completo' => 'Ana Perez']);
    $eliminado = Empleado::factory()->create();

    SolicitudVacacion::create(['empleado_id' => $empleado->id, 'fecha_inicio' => '2026-04-06', 'fecha_fin' => '2026-04-08', 'dias_solicitados' => 3.5, 'estado' => 'aprobado']);
    SolicitudVacacion::create(['empleado_id' => $empleado->id, 'fecha_inicio' => '2026-08-03', 'fecha_fin' => '2026-08-03', 'dias_solicitados' => 1, 'estado' => 'aprobado']);
    SolicitudVacacion::create(['empleado_id' => $eliminado->id, 'fecha_inicio' => '2026-04-06', 'fecha_fin' => '2026-04-06', 'dias_solicitados' => 1, 'estado' => 'aprobado']);
    SolicitudCompensacion::create(['empleado_id' => $empleado->id, 'fecha_compensacion' => '2026-04-10', 'horas_solicitadas' => 4.5, 'estado' => 'aprobado']);

    $eliminado->delete();

    $this->getJson('/api/v1/integracion/solicitudes-vacaciones')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson('/api/v1/integracion/solicitudes-vacaciones?desde=2026-04-01&hasta=2026-04-30')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.empleado.nombre_completo', 'Ana Perez')
        ->assertJsonPath('data.0.dias_solicitados', 3.5)
        ->assertJsonPath('data.0.texto', '3 días y 4 horas');

    $this->getJson('/api/v1/integracion/solicitudes-compensaciones?empleado_id='.$empleado->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.horas_solicitadas', 4.5)
        ->assertJsonPath('data.0.texto', '4 horas y 30 minutos');
});

it('valida los parametros de los listados', function () {
    autenticarIntegracion();

    $this->getJson('/api/v1/integracion/solicitudes-vacaciones?desde=2026-05-01&hasta=2026-04-01')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('hasta');

    $this->getJson('/api/v1/integracion/empleados?per_page=500')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

it('crea el usuario tecnico de integracion una sola vez', function () {
    config(['services.rrhh_integracion.user_email' => 'integracion@internal.local']);

    $this->artisan('app:crear-usuario-integracion')->assertSuccessful();
    $this->artisan('app:crear-usuario-integracion')->assertFailed();

    expect(User::where('email', 'integracion@internal.local')->count())->toBe(1);
});
