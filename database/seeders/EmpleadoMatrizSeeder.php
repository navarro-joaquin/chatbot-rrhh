<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\Gestion;
use App\Models\Vacacion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmpleadoMatrizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Creamos las gestiones (puedes añadir más años si deseas)
        $gestion2025 = Gestion::create(['anio' => 2025]);

        // 2. Definimos los empleados de tu imagen
        $empleados = [
            [
                'nombre_completo' => 'Perez Peralta Gonzalo',
                'carnet_identidad' => '1234567', // Datos ficticios para el test
                'telefono' => '59170000001',
                'fecha_contratacion' => '1987-09-01',
                'dias' => 23,
            ],
            [
                'nombre_completo' => 'Meneses Coronado Desiderio',
                'carnet_identidad' => '2345678',
                'telefono' => '59170000002',
                'fecha_contratacion' => '1989-03-20',
                'dias' => 19,
            ],
            [
                'nombre_completo' => 'Portugal Cueto Ronald',
                'carnet_identidad' => '3456789',
                'telefono' => '59170000003',
                'fecha_contratacion' => '1991-01-07',
                'dias' => 14.5,
            ],
        ];

        foreach ($empleados as $data) {
            // Creamos el empleado
            $empleado = Empleado::create([
                'nombre_completo' => $data['nombre_completo'],
                'carnet_identidad' => $data['carnet_identidad'],
                'telefono' => $data['telefono'],
                'fecha_contratacion' => $data['fecha_contratacion'],
                'tipo' => 'Planta',
                'estado' => true,
            ]);

            // Creamos su registro de vacación para la gestión 2025 (la celda del Excel)
            Vacacion::create([
                'empleado_id' => $empleado->id,
                'gestion_id' => $gestion2025->id,
                'dias_disponibles' => $data['dias'],
            ]);
        }
    }
}
