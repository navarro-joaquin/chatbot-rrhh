<?php

namespace App\Http\Controllers\Api\Bot;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
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

        return response()->json([
            'data' => $vacaciones->map(fn ($vacacion) => [
                'gestion' => $vacacion->gestion?->anio,
                'dias_disponibles' => (float) $vacacion->dias_disponibles,
            ]),
            'meta' => [
                'total_dias_disponibles' => (float) $vacaciones->sum('dias_disponibles'),
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

        return response()->json([
            'data' => $compensaciones->map(fn ($compensacion) => [
                'gestion' => $compensacion->gestion?->anio,
                'cantidad_horas' => (float) $compensacion->cantidad_horas,
                'fecha_registro' => $compensacion->fecha_registro?->toDateString(),
            ]),
            'meta' => [
                'total_horas_disponibles' => (float) $compensaciones->sum('cantidad_horas'),
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
            'data' => $solicitudes->map(fn ($solicitud) => [
                'id' => $solicitud->id,
                'fecha_inicio' => $solicitud->fecha_inicio?->toDateString(),
                'fecha_fin' => $solicitud->fecha_fin?->toDateString(),
                'dias_solicitados' => (float) $solicitud->dias_solicitados,
                'estado' => $solicitud->estado,
            ]),
        ]);
    }

    public function solicitudesCompensaciones(int $empleado): JsonResponse
    {
        $empleadoActivo = $this->buscarEmpleadoActivo($empleado);
        $solicitudes = $empleadoActivo->solicitudesCompensaciones()
            ->latest()
            ->get();

        return response()->json([
            'data' => $solicitudes->map(fn ($solicitud) => [
                'id' => $solicitud->id,
                'fecha_compensacion' => $solicitud->fecha_compensacion?->toDateString(),
                'horas_solicitadas' => (float) $solicitud->horas_solicitadas,
                'estado' => $solicitud->estado,
            ]),
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
