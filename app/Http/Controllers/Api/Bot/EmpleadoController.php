<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Support\VacacionesTiempo;
use Illuminate\Http\JsonResponse;

class EmpleadoController extends Controller
{
    public function porTelefono(string $telefono): JsonResponse
    {
        $telefonoNormalizado = $this->normalizarTelefono($telefono);

        $empleado = Empleado::query()
            ->with('contratoVigente')
            ->where('telefono', $telefonoNormalizado)
            ->where('estado', true)
            ->first();

        if (! $empleado) {
            return response()->json([
                'message' => 'Empleado activo no encontrado.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $empleado->id,
                'nombre_completo' => $empleado->nombre_completo,
                'telefono' => $empleado->telefono,
                'contrato_vigente' => $empleado->contratoVigente ? [
                    'id' => $empleado->contratoVigente->id,
                    'nro_item' => $empleado->contratoVigente->nro_item,
                    'tipo' => $empleado->contratoVigente->tipo,
                ] : null,
            ],
        ]);
    }

    public function vacaciones(int $empleado): JsonResponse
    {
        $empleadoActivo = $this->buscarEmpleadoActivo($empleado);
        $vacaciones = $empleadoActivo->vacaciones()
            ->with('gestion')
            ->get()
            ->sortBy(fn ($vacacion) => $vacacion->gestion?->anio)
            ->values();

        $totalDias = (float) $vacaciones->sum('dias_disponibles');
        $partesTotal = VacacionesTiempo::aPartesDias($totalDias);

        return response()->json([
            'data' => $vacaciones->map(function ($vacacion) {
                $dias = (float) $vacacion->dias_disponibles;
                $partes = VacacionesTiempo::aPartesDias($dias);

                return [
                    'gestion' => $vacacion->gestion?->anio,
                    'dias_disponibles' => $dias,
                    'dias' => $partes['dias'],
                    'horas' => $partes['horas'],
                    'minutos' => $partes['minutos'],
                    'texto' => VacacionesTiempo::aTextoDias($dias),
                ];
            }),
            'meta' => [
                'total_dias_disponibles' => $totalDias,
                'total_dias' => $partesTotal['dias'],
                'total_horas' => $partesTotal['horas'],
                'total_minutos' => $partesTotal['minutos'],
                'total_texto' => VacacionesTiempo::aTextoDias($totalDias),
            ],
        ]);
    }

    public function compensaciones(int $empleado): JsonResponse
    {
        $empleadoActivo = $this->buscarEmpleadoActivo($empleado);
        $compensaciones = $empleadoActivo->compensaciones()
            ->with('gestion')
            ->where('estado', 'disponible')
            ->orderBy('fecha_registro')
            ->get();

        $totalHoras = (float) $compensaciones->sum('cantidad_horas');
        $partesTotal = VacacionesTiempo::aPartesHoras($totalHoras);

        return response()->json([
            'data' => $compensaciones->map(function ($compensacion) {
                $horas = (float) $compensacion->cantidad_horas;
                $partes = VacacionesTiempo::aPartesHoras($horas);

                return [
                    'gestion' => $compensacion->gestion?->anio,
                    'cantidad_horas' => $horas,
                    'horas' => $partes['horas'],
                    'minutos' => $partes['minutos'],
                    'texto' => VacacionesTiempo::aTextoHoras($horas),
                    'fecha_registro' => $compensacion->fecha_registro?->toDateString(),
                ];
            }),
            'meta' => [
                'total_horas_disponibles' => $totalHoras,
                'total_horas' => $partesTotal['horas'],
                'total_minutos' => $partesTotal['minutos'],
                'total_texto' => VacacionesTiempo::aTextoHoras($totalHoras),
            ],
        ]);
    }

    public function solicitudesVacaciones(int $empleado): JsonResponse
    {
        $empleadoActivo = $this->buscarEmpleadoActivo($empleado);
        $solicitudes = $empleadoActivo->solicitudesVacaciones()
            ->latest()
            ->get();

        return response()->json([
            'data' => $solicitudes->map(function ($solicitud) {
                $dias = (float) $solicitud->dias_solicitados;
                $partes = VacacionesTiempo::aPartesDias($dias);

                return [
                    'id' => $solicitud->id,
                    'fecha_inicio' => $solicitud->fecha_inicio?->toDateString(),
                    'fecha_fin' => $solicitud->fecha_fin?->toDateString(),
                    'dias_solicitados' => $dias,
                    'dias' => $partes['dias'],
                    'horas' => $partes['horas'],
                    'minutos' => $partes['minutos'],
                    'texto' => VacacionesTiempo::aTextoDias($dias),
                    'estado' => $solicitud->estado,
                ];
            }),
        ]);
    }

    public function solicitudesCompensaciones(int $empleado): JsonResponse
    {
        $empleadoActivo = $this->buscarEmpleadoActivo($empleado);
        $solicitudes = $empleadoActivo->solicitudesCompensaciones()
            ->latest()
            ->get();

        return response()->json([
            'data' => $solicitudes->map(function ($solicitud) {
                $horas = (float) $solicitud->horas_solicitadas;
                $partes = VacacionesTiempo::aPartesHoras($horas);

                return [
                    'id' => $solicitud->id,
                    'fecha_compensacion' => $solicitud->fecha_compensacion?->toDateString(),
                    'horas_solicitadas' => $horas,
                    'horas' => $partes['horas'],
                    'minutos' => $partes['minutos'],
                    'texto' => VacacionesTiempo::aTextoHoras($horas),
                    'estado' => $solicitud->estado,
                ];
            }),
        ]);
    }

    private function buscarEmpleadoActivo(int $empleado): Empleado
    {
        return Empleado::query()
            ->whereKey($empleado)
            ->where('estado', true)
            ->firstOrFail();
    }

    private function normalizarTelefono(string $telefono): string
    {
        $telefono = str_replace('@s.whatsapp.net', '', $telefono);
        $numero = preg_replace('/\D+/', '', $telefono) ?? '';

        if (str_starts_with($numero, '591')) {
            $numero = substr($numero, 3);
        }

        return $numero;
    }
}
