<?php

use App\Livewire\CompensacionTable;
use App\Livewire\ConsolidacionVacacionTable;
use App\Livewire\SolicitudCompensacionTable;
use App\Livewire\SolicitudVacacionTable;
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
        ->call('sortBy', 'vacaciones.empleado_id')
        ->assertSee('Ana Orden')
        ->assertSee('Victor Orden');
});

it('ordena las vacaciones por id de empleado y luego por gestion', function () {
    $gestion2024 = Gestion::create(['anio' => 2024]);
    $gestion2025 = Gestion::create(['anio' => 2025]);
    $gestion2026 = Gestion::create(['anio' => 2026]);

    // Zoe se registra primero (id menor), así que va antes que Ana aunque su nombre sea posterior.
    $zoe = crearEmpleadoBusqueda('Zoe Orden', '04');
    $ana = crearEmpleadoBusqueda('Ana Orden', '05');

    Vacacion::create(['empleado_id' => $ana->id, 'gestion_id' => $gestion2026->id, 'dias_disponibles' => 2]);
    Vacacion::create(['empleado_id' => $zoe->id, 'gestion_id' => $gestion2025->id, 'dias_disponibles' => 1]);
    Vacacion::create(['empleado_id' => $ana->id, 'gestion_id' => $gestion2024->id, 'dias_disponibles' => 3]);
    Vacacion::create(['empleado_id' => $zoe->id, 'gestion_id' => $gestion2024->id, 'dias_disponibles' => 4]);

    Livewire::test(VacacionTable::class)
        ->assertSet('sortField', 'vacaciones.empleado_id')
        ->assertSeeInOrder(['Zoe Orden', '2024', 'Zoe Orden', '2025', 'Ana Orden', '2024', 'Ana Orden', '2026']);
});

it('ordena el detalle de vacaciones del empleado por gestion', function () {
    $gestion2025 = Gestion::create(['anio' => 2025]);
    $gestion2026 = Gestion::create(['anio' => 2026]);

    $empleado = crearEmpleadoBusqueda('Luis Detalle', '06');

    Vacacion::create(['empleado_id' => $empleado->id, 'gestion_id' => $gestion2026->id, 'dias_disponibles' => 2]);
    Vacacion::create(['empleado_id' => $empleado->id, 'gestion_id' => $gestion2025->id, 'dias_disponibles' => 3]);

    Livewire::test(VacacionTable::class, ['empleadoId' => $empleado->id, 'isDetailView' => true])
        ->assertSet('sortField', 'gestiones.anio')
        ->assertSeeInOrder(['2025', '2026']);
});

it('la busqueda excluye las vacaciones que no coinciden', function () {
    $gestion2025 = Gestion::create(['anio' => 2025]);
    $gestion2026 = Gestion::create(['anio' => 2026]);

    $victor = crearEmpleadoBusqueda('Victor Filtro', '07');
    $ana = crearEmpleadoBusqueda('Ana Filtro', '08');

    Vacacion::create(['empleado_id' => $victor->id, 'gestion_id' => $gestion2026->id, 'dias_disponibles' => 5.25]);
    Vacacion::create(['empleado_id' => $ana->id, 'gestion_id' => $gestion2025->id, 'dias_disponibles' => 7.75]);

    Livewire::test(VacacionTable::class)
        ->set('search', 'victor')
        ->assertSee('5.2500')
        ->assertDontSee('7.7500')
        ->set('search', '2025')
        ->assertSee('7.7500')
        ->assertDontSee('5.2500');
});

it('la busqueda respeta el empleado del detalle', function () {
    $gestion = Gestion::create(['anio' => 2026]);

    $ana = crearEmpleadoBusqueda('Ana Detalle', '09');
    $otro = crearEmpleadoBusqueda('Pedro Detalle', '10');

    Vacacion::create(['empleado_id' => $ana->id, 'gestion_id' => $gestion->id, 'dias_disponibles' => 5.25]);
    Vacacion::create(['empleado_id' => $otro->id, 'gestion_id' => $gestion->id, 'dias_disponibles' => 7.75]);

    Livewire::test(VacacionTable::class, ['empleadoId' => $ana->id, 'isDetailView' => true])
        ->set('search', '2026')
        ->assertSee('5.2500')
        ->assertDontSee('7.7500');
});

it('busca sin errores en las demas tablas', function (string $tabla) {
    Livewire::test($tabla)
        ->set('search', 'ana')
        ->assertOk();
})->with([
    CompensacionTable::class,
    ConsolidacionVacacionTable::class,
    SolicitudCompensacionTable::class,
    SolicitudVacacionTable::class,
]);
