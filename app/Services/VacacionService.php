<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\SolicitudVacacion;
use App\Models\Vacacion;
use Illuminate\Support\Facades\DB;

class VacacionService
{
    /**
     * Registra una nueva solicitud de vacación y descuenta los días disponibles.
     */
    public function registrarSolicitud(array $data): SolicitudVacacion
    {
        return DB::transaction(function () use ($data) {
            $empleado = Empleado::findOrFail($data['empleado_id']);
            $diasARestar = (float) $data['dias_solicitados'];

            // 1. Crear el registro de la solicitud
            $solicitud = SolicitudVacacion::create([
                'empleado_id' => $empleado->id,
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
                'dias_solicitados' => $diasARestar,
                'motivo' => $data['motivo'] ?? null,
                'estado' => 'aprobado',
            ]);

            // 2. Descontar los días de la tabla 'vacaciones' (FIFO)
            $this->descontarDias($empleado->id, $diasARestar);

            return $solicitud;
        });
    }

    /**
     * Descuenta días de vacación empezando por la gestión más antigua.
     */
    private function descontarDias(int $empleadoId, float $cantidad): void
    {
        // Obtenemos las vacaciones ordenadas por la gestión (suponiendo que 'gestion_id' o el año de la gestión indica el orden)
        $vacacionesDisponibles = Vacacion::where('empleado_id', $empleadoId)
            ->where('dias_disponibles', '>', 0)
            ->join('gestiones', 'vacaciones.gestion_id', '=', 'gestiones.id')
            ->orderBy('gestiones.anio', 'asc')
            ->select('vacaciones.*')
            ->get();

        $restante = $cantidad;

        foreach ($vacacionesDisponibles as $vacacion) {
            if ($restante <= 0) {
                break;
            }

            if ($vacacion->dias_disponibles >= $restante) {
                // Si la gestión actual cubre todo lo que falta
                $vacacion->decrement('dias_disponibles', $restante);
                $restante = 0;
            } else {
                // Si no cubre todo, vaciamos esta gestión y seguimos con la siguiente
                $restante -= $vacacion->dias_disponibles;
                $vacacion->update(['dias_disponibles' => 0]);
            }
        }

        if ($restante > 0) {
            // Opcional: Manejar caso donde no hay suficientes días (aunque debería validarse antes)
            \Log::warning("El empleado #{$empleadoId} solicitó más días de los disponibles. Quedaron {$restante} días sin descontar de ninguna gestión.");
        }
    }

    /**
     * Calcula cuántos días tiene disponibles un empleado en total.
     */
    public function obtenerTotalDiasDisponibles(int $empleadoId): float
    {
        return (float) Vacacion::where('empleado_id', $empleadoId)->sum('dias_disponibles');
    }
}
