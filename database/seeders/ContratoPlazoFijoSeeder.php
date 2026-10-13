<?php

namespace Database\Seeders;

use App\Models\EmpleadoContrato;
use Illuminate\Database\Seeder;

class ContratoPlazoFijoSeeder extends Seeder
{
    /**
     * Carga inicial de contratos a plazo fijo (un vigente por empleado).
     * Fuente: plazo fijo.txt (empleados 157 al 211).
     * Requiere haber corrido PersonalSeeder primero.
     * Idempotente: actualiza por empleado_id y es_vigente.
     */
    public function run(): void
    {
        $contratos = [
            ['empleado_id' => 157, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-05', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 158, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 159, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-05', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 160, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-16', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 161, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-20', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 162, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-04-06', 'fecha_fin' => '2027-03-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 163, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-05-12', 'fecha_fin' => '2027-03-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 164, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-08', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 165, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-08', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 166, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-08', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 167, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-08', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 168, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-08', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 169, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-13', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 170, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-13', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 171, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-09', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 172, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-09', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 173, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-13', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 174, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-13', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 175, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-13', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 176, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-21', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 177, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-26', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 178, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-26', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 179, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-02-02', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 180, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-02-04', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 181, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-02-05', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 182, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-02-05', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 183, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-02-05', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 184, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-02-05', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 185, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-02-05', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 186, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-02-05', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 187, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-05-04', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 188, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-05-04', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 189, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 190, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-12', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 191, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-13', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 192, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-15', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 193, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-16', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 194, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-16', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 195, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-16', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 196, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-19', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 197, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-20', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 198, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-20', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 199, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-20', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 200, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-21', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 201, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-20', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 202, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-27', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 203, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-20', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 204, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-02-04', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 205, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-02-09', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 206, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-05', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 207, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-05', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 208, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-13', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 209, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-15', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 210, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-13', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
            ['empleado_id' => 211, 'tipo' => 'Plazo Fijo', 'numero_contrato' => null, 'nro_item' => null, 'fecha_inicio' => '2026-01-26', 'fecha_fin' => '2026-12-31', 'estado' => 'Vigente', 'es_vigente' => true, 'resolucion' => null],
        ];

        foreach ($contratos as $data) {
            EmpleadoContrato::updateOrCreate(
                ['empleado_id' => $data['empleado_id'], 'es_vigente' => true],
                [
                    'tipo' => $data['tipo'],
                    'numero_contrato' => $data['numero_contrato'],
                    'nro_item' => $data['nro_item'],
                    'fecha_inicio' => $data['fecha_inicio'],
                    'fecha_fin' => $data['fecha_fin'],
                    'estado' => $data['estado'],
                    'es_vigente' => $data['es_vigente'],
                    'resolucion' => $data['resolucion'],
                ]
            );
        }
    }
}
