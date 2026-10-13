<?php

use App\Livewire\EmpleadoTable;
use App\Livewire\VacacionTable;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Gestion;
use App\Models\User;
use App\Models\Vacacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->empleado = Empleado::factory()->create(['nombre_completo' => 'Empleado Eliminado']);
    $this->otro = Empleado::factory()->create(['nombre_completo' => 'Empleado Vigente']);

    $gestion = Gestion::create(['anio' => 2026]);

    foreach ([$this->empleado, $this->otro] as $empleado) {
        EmpleadoContrato::create([
            'empleado_id' => $empleado->id,
            'tipo' => 'Plazo Fijo',
            'numero_contrato' => 'C-'.$empleado->id,
            'fecha_inicio' => now()->subYear()->toDateString(),
            'fecha_fin' => now()->addDays(10)->toDateString(),
            'estado' => 'Vigente',
            'es_vigente' => true,
        ]);

        Vacacion::create([
            'empleado_id' => $empleado->id,
            'gestion_id' => $gestion->id,
            'dias_disponibles' => 15,
        ]);
    }
});

it('elimina al empleado de forma logica y conserva su historial', function () {
    Livewire::test('empleados')
        ->call('confirmDelete', $this->empleado->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Empleado::find($this->empleado->id))->toBeNull()
        ->and(Empleado::withTrashed()->find($this->empleado->id)?->trashed())->toBeTrue()
        ->and(Vacacion::where('empleado_id', $this->empleado->id)->count())->toBe(1)
        ->and(EmpleadoContrato::where('empleado_id', $this->empleado->id)->count())->toBe(1);
});

it('oculta los registros del empleado eliminado en las tablas y el dashboard', function () {
    $this->empleado->delete();

    Livewire::test(VacacionTable::class)
        ->assertSee('Empleado Vigente')
        ->assertDontSee('Empleado Eliminado');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Empleado Vigente')
        ->assertDontSee('Empleado Eliminado');

    $this->get(route('empleados.show', $this->empleado->id))->assertNotFound();
});

it('muestra los empleados eliminados solo al administrador', function () {
    $this->empleado->delete();

    Livewire::test(EmpleadoTable::class, ['soloEliminados' => true])
        ->assertDontSee('Empleado Eliminado');

    $this->actingAs(User::factory()->administrador()->create());

    Livewire::test(EmpleadoTable::class, ['soloEliminados' => true])
        ->assertSee('Empleado Eliminado')
        ->assertSee('Eliminado el')
        ->assertSee('Restaurar', stripInitialData: false)
        ->assertDontSee('Ver Detalle', stripInitialData: false)
        ->assertDontSee('Empleado Vigente');

    Livewire::test(EmpleadoTable::class)
        ->assertSee('Empleado Vigente')
        ->assertDontSee('Empleado Eliminado');
});

it('permite restaurar un empleado eliminado solo al administrador', function () {
    $this->empleado->delete();

    Livewire::test('empleados')
        ->assertDontSee('Ver empleados eliminados')
        ->call('confirmRestore', $this->empleado->id)
        ->assertForbidden();

    Livewire::test('empleados')
        ->set('restaurarId', $this->empleado->id)
        ->call('restore')
        ->assertForbidden();

    expect(Empleado::find($this->empleado->id))->toBeNull();

    $this->actingAs(User::factory()->administrador()->create());

    Livewire::test('empleados')
        ->assertSee('Ver empleados eliminados')
        ->call('confirmRestore', $this->empleado->id)
        ->assertSet('showRestoreModal', true)
        ->assertSee('Empleado Eliminado')
        ->call('restore')
        ->assertSet('showRestoreModal', false)
        ->assertHasNoErrors();

    expect(Empleado::find($this->empleado->id))->not->toBeNull();
});

it('avisa que el empleado fue eliminado al intentar darlo de alta otra vez', function () {
    $this->empleado->delete();
    $fecha = $this->empleado->fresh()->deleted_at->format('d/m/Y');

    Livewire::test('empleados')
        ->call('create')
        ->set('form.nombre_completo', 'Persona Repetida')
        ->set('form.carnet_identidad', $this->empleado->carnet_identidad)
        ->set('form.telefono', '79998877')
        ->call('save')
        ->assertHasErrors('form.carnet_identidad')
        ->assertSee("El empleado fue eliminado en fecha {$fecha}, contáctate con DTIC para restaurarlo.");

    expect(Empleado::withTrashed()->count())->toBe(2);
});
