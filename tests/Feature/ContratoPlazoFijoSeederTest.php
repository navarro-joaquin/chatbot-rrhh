<?php

use App\Models\EmpleadoContrato;
use Database\Seeders\ContratoPlazoFijoSeeder;
use Database\Seeders\PersonalSeeder;

test('seeder de contratos a plazo fijo carga los contratos correctamente', function () {
    $this->seed(PersonalSeeder::class);
    $this->seed(ContratoPlazoFijoSeeder::class);

    expect(EmpleadoContrato::where('tipo', 'Plazo Fijo')->count())->toBe(55);

    $contrato157 = EmpleadoContrato::where('empleado_id', 157)->first();
    expect($contrato157)->not->toBeNull();
    expect($contrato157->tipo)->toBe('Plazo Fijo');
    expect($contrato157->fecha_inicio->format('Y-m-d'))->toBe('2026-01-05');
    expect($contrato157->fecha_fin->format('Y-m-d'))->toBe('2026-12-31');
    expect($contrato157->es_vigente)->toBeTrue();
    expect($contrato157->estado)->toBe('Vigente');

    $contrato162 = EmpleadoContrato::where('empleado_id', 162)->first();
    expect($contrato162->fecha_fin->format('Y-m-d'))->toBe('2027-03-31');

    $contrato211 = EmpleadoContrato::where('empleado_id', 211)->first();
    expect($contrato211)->not->toBeNull();
    expect($contrato211->fecha_inicio->format('Y-m-d'))->toBe('2026-01-26');
    expect($contrato211->fecha_fin->format('Y-m-d'))->toBe('2026-12-31');
});

test('seeder de contratos a plazo fijo es idempotente', function () {
    $this->seed(PersonalSeeder::class);
    $this->seed(ContratoPlazoFijoSeeder::class);
    $this->seed(ContratoPlazoFijoSeeder::class);

    expect(EmpleadoContrato::where('tipo', 'Plazo Fijo')->count())->toBe(55);
});
