<?php

namespace App\Services;

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\SolicitudCompensacion;
use Illuminate\Support\Facades\DB;

class CompensacionService
{
    /**
     * Registra una nueva solicitud de compensacion y descuenta las horas disponibles.
     */
    public function registrarSolicitud(array $data): SolicitudCompensacion
    {
        return DB::transaction(function () use ($data) {
            $empleado = Empleado::findOrFail($data['empleado_id']);
            $horasARestar = (float) $data['horas_solicitadas'];

            $solicitud = SolicitudCompensacion::create([
                'empleado_id' => $empleado->id,
                'fecha_compensacion' => $data['fecha_compensacion'],
                'horas_solicitadas' => $horasARestar,
                'motivo' => $data['motivo'] ?? null,
                'estado' => 'aprobado',
            ]);

            $this->descontarHoras($empleado->id, $horasARestar);

            return $solicitud;
        });
    }

    /**
     * Calcula cuantas horas de compensacion tiene disponibles un empleado en total.
     */
    public function obtenerTotalHorasDisponibles(int $empleadoId): float
    {
        return (float) Compensacion::query()
            ->where('empleado_id', $empleadoId)
            ->where('estado', 'disponible')
            ->sum('cantidad_horas');
    }

    /**
     * Descuenta horas de compensacion empezando por la gestion y registro mas antiguos.
     */
    private function descontarHoras(int $empleadoId, float $cantidad): void
    {
        $compensacionesDisponibles = Compensacion::query()
            ->where('empleado_id', $empleadoId)
            ->where('estado', 'disponible')
            ->where('cantidad_horas', '>', 0)
            ->join('gestiones', 'compensaciones.gestion_id', '=', 'gestiones.id')
            ->orderBy('gestiones.anio', 'asc')
            ->orderByRaw('CASE WHEN compensaciones.fecha_registro IS NULL THEN 1 ELSE 0 END')
            ->orderBy('compensaciones.fecha_registro', 'asc')
            ->orderBy('compensaciones.id', 'asc')
            ->select('compensaciones.*')
            ->get();

        $restante = $cantidad;

        foreach ($compensacionesDisponibles as $compensacion) {
            if ($restante <= 0) {
                break;
            }

            $horasDisponibles = (float) $compensacion->cantidad_horas;

            if ($horasDisponibles > $restante) {
                $compensacion->update([
                    'cantidad_horas' => $horasDisponibles - $restante,
                ]);
                $restante = 0;
                continue;
            }

            $restante -= $horasDisponibles;
            $compensacion->update([
                'cantidad_horas' => 0,
                'estado' => 'utilizado',
            ]);
        }

        if ($restante > 0) {
            \Log::warning("El empleado #{$empleadoId} solicito mas horas de compensacion de las disponibles. Quedaron {$restante} horas sin descontar.");
        }
    }
}
