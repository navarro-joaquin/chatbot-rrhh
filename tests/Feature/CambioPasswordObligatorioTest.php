<?php

use App\Http\Middleware\AsegurarCambioPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('redirige al cambio de contraseña al usuario marcado', function (string $ruta) {
    $this->actingAs(User::factory()->administrador()->create(['debe_cambiar_contrasena' => true]));

    $this->get(route($ruta))->assertRedirect(route('password.cambiar'));
})->with([
    'dashboard',
    'empleados.index',
    'users.index',
    'profile.edit',
    'security.edit',
]);

it('redirige al cambio de contraseña tras iniciar sesión', function () {
    $user = User::factory()->create(['debe_cambiar_contrasena' => true]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertRedirect(route('password.cambiar'));
    $this->get(route('password.cambiar'))->assertOk()->assertSee('Cambiar contraseña');
});

it('no afecta a los usuarios sin cambio pendiente', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('password.cambiar'))->assertRedirect(route('dashboard'));
});

it('exige iniciar sesión para cambiar la contraseña', function () {
    $this->get(route('password.cambiar'))->assertRedirect(route('login'));
});

it('permite definir la nueva contraseña y libera el acceso', function () {
    $user = User::factory()->create(['debe_cambiar_contrasena' => true]);
    $this->actingAs($user);

    Livewire::test('pages::auth.cambiar-password')
        ->set('password', 'nueva-clave-segura')
        ->set('password_confirmation', 'nueva-clave-segura')
        ->call('cambiar')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $user->refresh();

    expect($user->debe_cambiar_contrasena)->toBeFalse()
        ->and(Hash::check('nueva-clave-segura', $user->password))->toBeTrue();

    $this->get(route('dashboard'))->assertOk();
});

it('rechaza reutilizar la contraseña actual o no confirmarla', function () {
    $user = User::factory()->create(['debe_cambiar_contrasena' => true]);
    $this->actingAs($user);

    Livewire::test('pages::auth.cambiar-password')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('cambiar')
        ->assertHasErrors('password');

    Livewire::test('pages::auth.cambiar-password')
        ->set('password', 'nueva-clave-segura')
        ->set('password_confirmation', 'otra-clave')
        ->call('cambiar')
        ->assertHasErrors('password');

    expect($user->fresh()->debe_cambiar_contrasena)->toBeTrue();
});

it('aplica el cambio obligatorio también a las acciones de livewire', function () {
    expect(Livewire::getPersistentMiddleware())->toContain(AsegurarCambioPassword::class);
});

it('marca por defecto a los usuarios nuevos para cambiar la contraseña', function () {
    $this->actingAs(User::factory()->administrador()->create());

    Livewire::test('users')
        ->call('create')
        ->assertSet('form.debe_cambiar_contrasena', true)
        ->set('form.name', 'Nueva Persona')
        ->set('form.email', 'nueva@test.com')
        ->set('form.password', 'secreto-123')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::firstWhere('email', 'nueva@test.com')->debe_cambiar_contrasena)->toBeTrue();
});

it('permite al administrador activar o quitar el cambio obligatorio', function () {
    $this->actingAs(User::factory()->administrador()->create());
    $user = User::factory()->create();

    Livewire::test('users')
        ->call('edit', $user->id)
        ->assertSet('form.debe_cambiar_contrasena', false)
        ->set('form.debe_cambiar_contrasena', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->debe_cambiar_contrasena)->toBeTrue();

    Livewire::test('users')
        ->call('edit', $user->id)
        ->set('form.debe_cambiar_contrasena', false)
        ->call('save');

    expect($user->fresh()->debe_cambiar_contrasena)->toBeFalse();
});
