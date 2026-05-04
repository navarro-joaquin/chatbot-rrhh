<?php

namespace App\Services;

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\SolicitudCompensacion;
use App\Models\SolicitudCompensacionDetalle;
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

            $this->descontarHoras($empleado->id, $horasARestar, $solicitud);

            return $solicitud;
        });
    }

    /**
     * Actualiza una solicitud existente reajustando el saldo de compensaciones.
     */
    public function actualizarSolicitud(SolicitudCompensacion $solicitud, array $data): SolicitudCompensacion
    {
        return DB::transaction(function () use ($solicitud, $data) {
            $solicitud->loadMissing('detalles.compensacion');

            $this->restaurarHorasDeSolicitud($solicitud);

            $empleado = Empleado::findOrFail($data['empleado_id']);
            $horasARestar = (float) $data['horas_solicitadas'];

            $solicitud->update([
                'empleado_id' => $empleado->id,
                'fecha_compensacion' => $data['fecha_compensacion'],
                'horas_solicitadas' => $horasARestar,
                'motivo' => $data['motivo'] ?? null,
            ]);

            $this->descontarHoras($empleado->id, $horasARestar, $solicitud);

            return $solicitud->fresh();
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
    private function descontarHoras(int $empleadoId, float $cantidad, ?SolicitudCompensacion $solicitud = null): void
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
            $descuento = min($horasDisponibles, $restante);

            if ($descuento <= 0) {
                continue;
            }

            if ($horasDisponibles > $restante) {
                $compensacion->update([
                    'cantidad_horas' => $horasDisponibles - $restante,
                ]);
                $restante = 0;
            } else {
                $restante -= $horasDisponibles;
                $compensacion->update([
                    'cantidad_horas' => 0,
                    'estado' => 'utilizado',
                ]);
            }

            if ($solicitud) {
                SolicitudCompensacionDetalle::create([
                    'solicitud_compensacion_id' => $solicitud->id,
                    'compensacion_id' => $compensacion->id,
                    'horas_descontadas' => $descuento,
                ]);
            }
        }

        if ($restante > 0) {
            \Log::warning("El empleado #{$empleadoId} solicito mas horas de compensacion de las disponibles. Quedaron {$restante} horas sin descontar.");
        }
    }

    /**
     * Restaura al saldo las horas usadas por una solicitud antes de recalcularla.
     */
    private function restaurarHorasDeSolicitud(SolicitudCompensacion $solicitud): void
    {
        if ($solicitud->detalles->isNotEmpty()) {
            foreach ($solicitud->detalles as $detalle) {
                $detalle->compensacion?->update([
                    'cantidad_horas' => (float) $detalle->compensacion->cantidad_horas + (float) $detalle->horas_descontadas,
                    'estado' => 'disponible',
                ]);
            }

            $solicitud->detalles()->delete();

            return;
        }

        $this->restaurarHorasLegacy($solicitud);
    }

    /**
     * Fallback para solicitudes anteriores a la bitacora de descuentos.
     */
    private function restaurarHorasLegacy(SolicitudCompensacion $solicitud): void
    {
        $compensacion = Compensacion::query()
            ->where('empleado_id', $solicitud->empleado_id)
            ->join('gestiones', 'compensaciones.gestion_id', '=', 'gestiones.id')
            ->orderBy('gestiones.anio', 'asc')
            ->orderByRaw('CASE WHEN compensaciones.fecha_registro IS NULL THEN 1 ELSE 0 END')
            ->orderBy('compensaciones.fecha_registro', 'asc')
            ->orderBy('compensaciones.id', 'asc')
            ->select('compensaciones.*')
            ->first();

        if (! $compensacion) {
            return;
        }

        $compensacion->update([
            'cantidad_horas' => (float) $compensacion->cantidad_horas + (float) $solicitud->horas_solicitadas,
            'estado' => 'disponible',
        ]);

        \Log::warning('Solicitud de compensacion restaurada sin detalle historico.', [
            'solicitud_id' => $solicitud->id,
            'empleado_id' => $solicitud->empleado_id,
        ]);
    }
}
