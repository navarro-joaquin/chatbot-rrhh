<?php

use App\Livewire\HistorialVacacionTable;
use App\Models\Empleado;
use App\Models\Gestion;
use App\Models\User;
use App\Models\Vacacion;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $gestion = Gestion::create(['anio' => 2026]);

    $this->empleado = Empleado::factory()->create(['nombre_completo' => 'Ana Pérez Rojas']);
    $this->otro = Empleado::factory()->create();

    foreach ([$this->empleado, $this->otro] as $empleado) {
        Vacacion::create([
            'empleado_id' => $empleado->id,
            'gestion_id' => $gestion->id,
            'dias_disponibles' => 10,
        ]);
    }
});

it('no muestra registros mientras no se selecciona un empleado', function () {
    $tabla = Livewire::test(HistorialVacacionTable::class)->instance();

    expect($tabla->datasource()->count())->toBe(0);
});

it('muestra solo el historial del empleado seleccionado', function () {
    $tabla = Livewire::test(HistorialVacacionTable::class, ['empleadoId' => $this->empleado->id])->instance();

    expect($tabla->datasource()->pluck('empleado_id')->unique()->all())->toBe([$this->empleado->id]);
});

it('muestra los dias y el saldo en texto', function () {
    Livewire::test(HistorialVacacionTable::class, ['empleadoId' => $this->empleado->id])
        ->assertSee('10 días')
        ->assertDontSee('10.0000');
});

it('exporta el excel con el nombre del empleado en el archivo y como encabezado', function () {
    $archivo = 'historial_vacaciones_ana_perez_rojas.xlsx';

    $descarga = Livewire::test(HistorialVacacionTable::class, ['empleadoId' => $this->empleado->id])
        ->call('exportToXLS')
        ->assertFileDownloaded($archivo)
        ->effects['download'];

    $ruta = tempnam(sys_get_temp_dir(), 'historial');
    file_put_contents($ruta, base64_decode($descarga['content']));

    $lector = new Reader(new Options(SHOULD_PRESERVE_EMPTY_ROWS: true));
    $lector->open($ruta);

    $filas = [];
    foreach ($lector->getSheetIterator() as $hoja) {
        foreach ($hoja->getRowIterator() as $fila) {
            $filas[] = $fila->toArray();
        }
    }

    $lector->close();
    unlink($ruta);

    expect($filas[0][0])->toBe('Historial de vacaciones')
        ->and($filas[1][0])->toBe('Empleado: Ana Pérez Rojas')
        ->and($filas[3][0])->toBe('Evento')
        ->and($filas[4][0])->toBe('Saldo inicial / ajuste manual')
        ->and($filas[4][3])->toBe('10 días');
});

it('el filtro del reporte no ofrece la opcion de todos los empleados', function () {
    $this->get(route('reportes.historial-vacaciones'))
        ->assertOk()
        ->assertDontSee('Todos los empleados')
        ->assertSee('Seleccione un empleado...');
});
