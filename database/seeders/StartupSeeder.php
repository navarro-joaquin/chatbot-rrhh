<?php

namespace Database\Seeders;

use App\Models\Antiguedad;
use App\Models\Gestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StartupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gestiones = [
            ['anio' => '2016'],
            ['anio' => '2017'],
            ['anio' => '2018'],
            ['anio' => '2019'],
            ['anio' => '2020'],
            ['anio' => '2021'],
            ['anio' => '2022'],
            ['anio' => '2023'],
            ['anio' => '2024'],
            ['anio' => '2025'],
            ['anio' => '2026'],
        ];

        $antiguedades = [
            [
                'anios_desde' => 1,
                'anios_hasta' => 4,
                'dias_asignados' => 15,
            ],
            [
                'anios_desde' => 5,
                'anios_hasta' => 10,
                'dias_asignados' => 20,
            ],
            [
                'anios_desde' => 11,
                'anios_hasta' => 99,
                'dias_asignados' => 30,
            ],
        ];

        foreach ($gestiones as $data) {
            Gestion::create([
                'anio' => $data['anio'],
            ]);
        }

        foreach ($antiguedades as $data) {
            Antiguedad::create([
                'anios_desde' => $data['anios_desde'],
                'anios_hasta' => $data['anios_hasta'],
                'dias_asignados' => $data['dias_asignados'],
            ]);
        }

        User::create([
            'name' => 'Administrador',
            'email' => 'admin@admin.com',
            'password' => Hash::make('Passw0rd'),
        ]);
    }
}
