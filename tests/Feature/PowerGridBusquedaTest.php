<?php

use App\Livewire\VacacionTable;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Gestion;
use App\Models\Vacacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function crearEmpleadoBusqueda(string $nombre, string $sufijo): Empleado
{
    $empleado = Empleado::create([
        'nombre_completo' => $nombre,
        'carnet_identidad' => 'CI-BUSQ-'.$sufijo,
        'telefono' => '7000BUSQ'.$sufijo,
        'estado' => true,
    ]);

    EmpleadoContrato::create([
        'empleado_id' => $empleado->id,
        'tipo' => 'Indefinido',
        'fecha_inicio' => '2020-01-01',
        'estado' => 'Vigente',
        'es_vigente' => true,
    ]);

    return $empleado;
}

it('busca vacaciones por nombre de empleado sin error de columna', function () {
    $gestion = Gestion::create(['anio' => 2026]);

    $victor = crearEmpleadoBusqueda('Victor Busqueda', '01');
    $otro = crearEmpleadoBusqueda('Ana Otra', '02');

    Vacacion::create(['empleado_id' => $victor->id, 'gestion_id' => $gestion->id, 'dias_disponibles' => 5]);
    Vacacion::create(['empleado_id' => $otro->id, 'gestion_id' => $gestion->id, 'dias_disponibles' => 7]);

    Livewire::test(VacacionTable::class)
        ->set('search', 'victor')
        ->assertSee('Victor Busqueda');
});

it('busca vacaciones por gestion sin error de columna', function () {
    $gestion2025 = Gestion::create(['anio' => 2025]);
    $gestion2026 = Gestion::create(['anio' => 2026]);

    $empleado = crearEmpleadoBusqueda('Pedro Gestion', '03');

    Vacacion::create(['empleado_id' => $empleado->id, 'gestion_id' => $gestion2025->id, 'dias_disponibles' => 5]);
    Vacacion::create(['empleado_id' => $empleado->id, 'gestion_id' => $gestion2026->id, 'dias_disponibles' => 7]);

    Livewire::test(VacacionTable::class)
        ->set('search', '2025')
        ->assertSee('2025');
});

it('ordena vacaciones por empleado sin error de columna', function () {
    $gestion = Gestion::create(['anio' => 2026]);

    $ana = crearEmpleadoBusqueda('Ana Orden', '04');
    $victor = crearEmpleadoBusqueda('Victor Orden', '05');

    Vacacion::create(['empleado_id' => $ana->id, 'gestion_id' => $gestion->id, 'dias_disponibles' => 5]);
    Vacacion::create(['empleado_id' => $victor->id, 'gestion_id' => $gestion->id, 'dias_disponibles' => 7]);

    Livewire::test(VacacionTable::class)
        ->call('sortBy', 'empleados.nombre_completo')
        ->assertSee('Ana Orden')
        ->assertSee('Victor Orden');
});
