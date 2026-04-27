<?php

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Gestion;
use App\Models\SolicitudCompensacion;
use App\Services\CompensacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('descuenta horas de compensacion usando FIFO por gestion y fecha', function () {
    $empleado = crearEmpleadoCompensacion('FIFO Compensacion', '3001');

    $gestion2025 = Gestion::create(['anio' => 2025]);
    $gestion2026 = Gestion::create(['anio' => 2026]);
    $contrato = $empleado->contratoVigente()->firstOrFail();

    Compensacion::create([
        'empleado_id' => $empleado->id,
        'gestion_id' => $gestion2025->id,
        'contrato_id' => $contrato->id,
        'cantidad_horas' => 2,
        'descripcion' => 'Saldo mas antiguo',
        'fecha_registro' => '2025-03-01',
        'estado' => 'disponible',
    ]);

    Compensacion::create([
        'empleado_id' => $empleado->id,
        'gestion_id' => $gestion2026->id,
        'contrato_id' => $contrato->id,
        'cantidad_horas' => 5,
        'descripcion' => 'Saldo reciente',
        'fecha_registro' => '2026-01-10',
        'estado' => 'disponible',
    ]);

    $service = app(CompensacionService::class);

    $service->registrarSolicitud([
        'empleado_id' => $empleado->id,
        'fecha_compensacion' => '2026-04-30',
        'horas_solicitadas' => 4,
        'motivo' => 'Salida medica',
    ]);

    $saldo2025 = Compensacion::where('empleado_id', $empleado->id)
        ->where('gestion_id', $gestion2025->id)
        ->first();

    $saldo2026 = Compensacion::where('empleado_id', $empleado->id)
        ->where('gestion_id', $gestion2026->id)
        ->first();

    $solicitud = SolicitudCompensacion::where('empleado_id', $empleado->id)->first();

    expect($solicitud)->not->toBeNull()
        ->and((float) $solicitud->horas_solicitadas)->toBe(4.0)
        ->and($solicitud->estado)->toBe('aprobado')
        ->and((float) $saldo2025->cantidad_horas)->toBe(0.0)
        ->and($saldo2025->estado)->toBe('utilizado')
        ->and((float) $saldo2026->cantidad_horas)->toBe(3.0)
        ->and($saldo2026->estado)->toBe('disponible')
        ->and($service->obtenerTotalHorasDisponibles($empleado->id))->toBe(3.0);
});

it('solo suma horas disponibles de compensacion', function () {
    $empleado = crearEmpleadoCompensacion('Totales Compensacion', '3002');

    $gestion2026 = Gestion::create(['anio' => 2026]);
    $contrato = $empleado->contratoVigente()->firstOrFail();

    Compensacion::create([
        'empleado_id' => $empleado->id,
        'gestion_id' => $gestion2026->id,
        'contrato_id' => $contrato->id,
        'cantidad_horas' => 6,
        'descripcion' => 'Horas vigentes',
        'fecha_registro' => '2026-02-15',
        'estado' => 'disponible',
    ]);

    Compensacion::create([
        'empleado_id' => $empleado->id,
        'gestion_id' => $gestion2026->id,
        'contrato_id' => $contrato->id,
        'cantidad_horas' => 8,
        'descripcion' => 'Horas usadas',
        'fecha_registro' => '2026-02-20',
        'estado' => 'utilizado',
    ]);

    Compensacion::create([
        'empleado_id' => $empleado->id,
        'gestion_id' => $gestion2026->id,
        'contrato_id' => $contrato->id,
        'cantidad_horas' => 10,
        'descripcion' => 'Horas vencidas',
        'fecha_registro' => '2026-02-25',
        'estado' => 'vencido',
    ]);

    $service = app(CompensacionService::class);

    expect($service->obtenerTotalHorasDisponibles($empleado->id))->toBe(6.0);
});

function crearEmpleadoCompensacion(string $nombre, string $sufijo): Empleado
{
    $empleado = Empleado::create([
        'nombre_completo' => $nombre,
        'carnet_identidad' => 'CI-'.$sufijo,
        'telefono' => '7111'.$sufijo,
        'correo_electronico' => 'comp'.$sufijo.'@test.com',
        'estado' => true,
    ]);

    EmpleadoContrato::create([
        'empleado_id' => $empleado->id,
        'tipo' => 'Planta',
        'numero_contrato' => null,
        'nro_item' => 'ITEM-'.$sufijo,
        'fecha_inicio' => '2025-01-01',
        'fecha_fin' => null,
        'estado' => 'Vigente',
        'es_vigente' => true,
        'resolucion' => 'RA-'.$sufijo,
    ]);

    return $empleado->fresh();
}
