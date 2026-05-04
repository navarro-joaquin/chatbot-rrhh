<?php

namespace App\Http\Controllers;

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Models\Feriado;
use App\Models\Gestion;
use App\Models\SolicitudCompensacion;
use App\Models\SolicitudVacacion;
use App\Models\Vacacion;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today();
        $currentGestion = Gestion::query()
            ->where('anio', $today->year)
            ->first()
            ?? Gestion::query()->latest('anio')->first();

        $empleadosActivos = Empleado::query()->where('estado', true)->count();
        $totalEmpleados = Empleado::query()->count();
        $contratosVigentes = EmpleadoContrato::query()->where('es_vigente', true)->count();
        $empleadosSinContratoVigente = Empleado::query()
            ->where('estado', true)
            ->whereDoesntHave('contratoVigente')
            ->count();

        $contratosPorVencer = EmpleadoContrato::query()
            ->where('es_vigente', true)
            ->whereNotNull('fecha_fin')
            ->whereBetween('fecha_fin', [$today, $today->copy()->addDays(30)])
            ->count();

        $vacacionesQuery = Vacacion::query();

        if ($currentGestion !== null) {
            $vacacionesQuery->where('gestion_id', $currentGestion->id);
        }

        $diasVacacionesDisponibles = (float) $vacacionesQuery->sum('dias_disponibles');
        $promedioVacaciones = (float) $vacacionesQuery->avg('dias_disponibles');

        $solicitudesMes = SolicitudVacacion::query()
            ->whereBetween('fecha_inicio', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
            ->count();

        $horasCompensacionDisponibles = (float) Compensacion::query()
            ->where('estado', 'disponible')
            ->sum('cantidad_horas');

        $solicitudesCompensacionMes = SolicitudCompensacion::query()
            ->whereBetween('fecha_compensacion', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
            ->count();

        $resumenContratos = EmpleadoContrato::query()
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->orderByDesc('total')
            ->get();

        $resumenTiposContrato = EmpleadoContrato::query()
            ->select('tipo', DB::raw('COUNT(*) as total'))
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->get();

        $resumenSolicitudes = SolicitudVacacion::query()
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->orderByDesc('total')
            ->get();

        $contratosProximosAVencer = EmpleadoContrato::query()
            ->with('empleado:id,nombre_completo')
            ->where('es_vigente', true)
            ->whereNotNull('fecha_fin')
            ->whereBetween('fecha_fin', [$today, $today->copy()->addDays(60)])
            ->orderBy('fecha_fin')
            ->limit(5)
            ->get();

        $feriadosProximos = Feriado::query()
            ->where('estado', true)
            ->whereDate('fecha', '>=', $today)
            ->orderBy('fecha')
            ->limit(5)
            ->get();

        $topSaldosVacaciones = Vacacion::query()
            ->join('empleados', 'vacaciones.empleado_id', '=', 'empleados.id')
            ->select(
                'vacaciones.empleado_id',
                'empleados.nombre_completo',
                DB::raw('SUM(vacaciones.dias_disponibles) as total_dias_disponibles')
            )
            ->groupBy('vacaciones.empleado_id', 'empleados.nombre_completo')
            ->orderByDesc('total_dias_disponibles')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'currentGestion' => $currentGestion,
            'empleadosActivos' => $empleadosActivos,
            'totalEmpleados' => $totalEmpleados,
            'contratosVigentes' => $contratosVigentes,
            'empleadosSinContratoVigente' => $empleadosSinContratoVigente,
            'contratosPorVencer' => $contratosPorVencer,
            'diasVacacionesDisponibles' => $diasVacacionesDisponibles,
            'promedioVacaciones' => $promedioVacaciones,
            'solicitudesMes' => $solicitudesMes,
            'horasCompensacionDisponibles' => $horasCompensacionDisponibles,
            'solicitudesCompensacionMes' => $solicitudesCompensacionMes,
            'resumenContratos' => $resumenContratos,
            'resumenTiposContrato' => $resumenTiposContrato,
            'resumenSolicitudes' => $resumenSolicitudes,
            'contratosProximosAVencer' => $contratosProximosAVencer,
            'feriadosProximos' => $feriadosProximos,
            'topSaldosVacaciones' => $topSaldosVacaciones,
        ]);
    }
}
