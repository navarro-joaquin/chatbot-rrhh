<?php

use App\Enums\Rol;
use App\Livewire\ActividadTable;
use App\Models\Gestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('permite al administrador gestionar los usuarios', function () {
    $this->actingAs(User::factory()->administrador()->create());

    $this->get(route('users.index'))->assertOk();
    $this->get(route('dashboard'))->assertSee(route('users.index'));
});

it('impide a recursos humanos ver los usuarios de la plataforma', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('users.index'))->assertForbidden();
    $this->get(route('dashboard'))->assertOk()->assertDontSee(route('users.index'));
});

it('permite a recursos humanos ver el resto de la informacion', function (string $ruta) {
    $this->actingAs(User::factory()->create());

    $this->get(route($ruta))->assertOk();
})->with([
    'empleados.index',
    'vacaciones.index',
    'solicitudes-vacaciones.index',
    'compensaciones.index',
    'solicitudes-compensaciones.index',
    'gestiones.index',
    'antiguedades.index',
    'feriados.index',
    'consolidacion-vacacion.index',
    'reportes.historial-vacaciones',
    'actividades.index',
]);

it('crea usuarios con el perfil seleccionado', function () {
    $this->actingAs(User::factory()->administrador()->create());

    Livewire::test('users')
        ->call('create')
        ->set('form.name', 'Nueva Persona')
        ->set('form.email', 'nueva@test.com')
        ->set('form.rol', Rol::Administrador->value)
        ->set('form.password', 'secreto-123')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::firstWhere('email', 'nueva@test.com')->rol)->toBe(Rol::Administrador);
});

it('impide que un administrador cambie su propio perfil', function () {
    $admin = User::factory()->administrador()->create();
    $this->actingAs($admin);

    Livewire::test('users')
        ->call('edit', $admin->id)
        ->set('form.rol', Rol::RecursosHumanos->value)
        ->call('save')
        ->assertHasErrors('form.rol');

    expect($admin->fresh()->rol)->toBe(Rol::Administrador);
});

it('oculta a recursos humanos la actividad sobre usuarios', function () {
    $admin = User::factory()->administrador()->create();
    $this->actingAs($admin);
    $admin->update(['name' => 'Nombre Cambiado']);
    Gestion::create(['anio' => 2026]);

    Livewire::test(ActividadTable::class)
        ->assertSee('User (#'.$admin->id.')')
        ->assertSee('Gestion (#');

    $this->actingAs(User::factory()->create());

    Livewire::test(ActividadTable::class)
        ->assertDontSee('User (#')
        ->assertSee('Gestion (#');
});
