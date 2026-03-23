<?php

namespace App\Console\Commands;

use App\Models\Antiguedad;
use App\Models\Empleado;
use App\Models\Gestion;
use App\Models\Vacacion;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcesarVacacionesAnuales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:procesar-vacaciones-anuales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registra automáticamente los días de vacaciones anuales según la antigüedad del empleado.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $today = Carbon::today();
        $this->info("Procesando vacaciones para el {$today->format('d/m')}...");

        // 1. Obtener la gestión actual (o crearla si no existe)
        $gestion = Gestion::firstOrCreate(['anio' => $today->year]);

        // 2. Buscar empleados activos que cumplan años hoy, con el tipo = Planta, y estado Activo
        $empleados = Empleado::where('estado', true)
            ->whereMonth('fecha_contratacion', $today->month)
            ->whereDay('fecha_contratacion', $today->day)
            ->where('tipo', 'like', 'Planta')
            ->where('estado', true)
            ->get();

        if ($empleados->isEmpty()) {
            $this->info('No hay aniversarios de contratación hoy.');

            return;
        }

        foreach ($empleados as $empleado) {
            // 3. Calcular antigüedad exacta en años (valor absoluto para evitar negativos)
            $antiguedadAnios = (int) Carbon::parse($empleado->fecha_contratacion)->diffInYears($today);

            // Solo procesamos si ya cumplió al menos 1 año
            if ($antiguedadAnios <= 0) {
                continue;
            }

            // 4. Buscar días correspondientes según tabla Antiguedad
            // Buscamos el rango: anios_desde <= antigüedad <= anios_hasta
            $rangoAntiguedad = Antiguedad::where('anios_desde', '<=', $antiguedadAnios)
                ->where('anios_hasta', '>=', $antiguedadAnios)
                ->first();

            if (! $rangoAntiguedad) {
                $this->warn("No se encontró rango para {$antiguedadAnios} años para el empleado: {$empleado->nombre_completo}");

                continue;
            }

            // 5. Registrar la vacación (firstOrCreate para evitar duplicados si el comando se corre dos veces)
            $vacacion = Vacacion::firstOrCreate([
                'empleado_id' => $empleado->id,
                'gestion_id' => $gestion->id,
            ], [
                'dias_disponibles' => $rangoAntiguedad->dias_asignados,
            ]);

            if ($vacacion->wasRecentlyCreated) {
                $this->info("Asignados {$rangoAntiguedad->dias_asignados} días a {$empleado->nombre_completo} ({$antiguedadAnios} años).");
                Log::info("Vacaciones automáticas: {$empleado->nombre_completo} recibió {$rangoAntiguedad->dias_asignados} días por aniversario.");
            } else {
                $this->info("Vacación ya existía para {$empleado->nombre_completo} en la gestión {$gestion->anio}.");
            }
        }

        $this->info('Proceso finalizado.');
    }
}
