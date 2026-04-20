<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Gestion;
use App\Models\Vacacion;
use Illuminate\Database\Seeder;

class EmpleadoMatrizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gestion2025 = Gestion::firstOrCreate(['anio' => 2025]);

        $empleados = [
            [
                'nombre_completo' => 'Perez Peralta Gonzalo',
                'carnet_identidad' => '1234567',
                'telefono' => '59170000001',
                'fecha_inicio_contrato' => '1987-09-01',
                'nro_item' => 'ITEM-0001',
                'dias' => 23,
            ],
            [
                'nombre_completo' => 'Meneses Coronado Desiderio',
                'carnet_identidad' => '2345678',
                'telefono' => '59170000002',
                'fecha_inicio_contrato' => '1989-03-20',
                'nro_item' => 'ITEM-0002',
                'dias' => 19,
            ],
            [
                'nombre_completo' => 'Portugal Cueto Ronald',
                'carnet_identidad' => '3456789',
                'telefono' => '59170000003',
                'fecha_inicio_contrato' => '1991-01-07',
                'nro_item' => 'ITEM-0003',
                'dias' => 14.5,
            ],
        ];

        foreach ($empleados as $data) {
            $empleado = Empleado::create([
                'nombre_completo' => $data['nombre_completo'],
                'carnet_identidad' => $data['carnet_identidad'],
                'telefono' => $data['telefono'],
                'estado' => true,
            ]);

            EmpleadoContrato::create([
                'empleado_id' => $empleado->id,
                'tipo' => 'Planta',
                'numero_contrato' => null,
                'nro_item' => $data['nro_item'],
                'fecha_inicio' => $data['fecha_inicio_contrato'],
                'fecha_fin' => null,
                'estado' => 'Vigente',
                'es_vigente' => true,
                'resolucion' => null,
            ]);

            Vacacion::create([
                'empleado_id' => $empleado->id,
                'gestion_id' => $gestion2025->id,
                'dias_disponibles' => $data['dias'],
            ]);
        }
    }
}
