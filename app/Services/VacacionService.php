<?php

namespace App\Services;

use App\Models\Antiguedad;
use App\Models\Empleado;
use App\Models\EmpleadoAntiguedad;
use App\Models\Feriado;
use App\Models\Gestion;
use App\Models\SolicitudVacacion;
use App\Models\Vacacion;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
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

            $solicitud = SolicitudVacacion::create([
                'empleado_id' => $empleado->id,
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
                'dias_solicitados' => $diasARestar,
                'motivo' => $data['motivo'] ?? null,
                'estado' => 'aprobado',
            ]);

            $this->descontarDias($empleado->id, $diasARestar);

            return $solicitud;
        });
    }

    /**
     * Procesa vacaciones automáticas para la fecha indicada.
     *
     * @return array<int, array{empleado:string, gestion:int, dias:float, origen:string, accion:string}>
     */
    public function procesarVacacionesAutomaticas(Carbon $fecha): array
    {
        $empleados = Empleado::query()
            ->with(['contratoVigente', 'antiguedadVigente'])
            ->where('estado', true)
            ->whereHas('contratoVigente', fn ($query) => $query->where('tipo', 'Planta'))
            ->get();

        $resultados = [];

        foreach ($empleados as $empleado) {
            $candidatos = $this->resolverCandidatosDelDia($empleado, $fecha);

            foreach ($candidatos as $candidato) {
                $resultado = $this->registrarVacacionAutomatica($empleado, $candidato);

                if ($resultado !== null) {
                    $resultados[] = $resultado;
                }
            }
        }

        return $resultados;
    }

    /**
     * Calcula cuántos días tiene disponibles un empleado en total.
     */
    public function obtenerTotalDiasDisponibles(int $empleadoId): float
    {
        return (float) Vacacion::where('empleado_id', $empleadoId)->sum('dias_disponibles');
    }

    /**
     * Calcula días hábiles entre dos fechas excluyendo fines de semana y feriados activos.
     */
    public function calcularDiasSolicitados(string $fechaInicio, string $fechaFin): float
    {
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->startOfDay();

        if ($inicio->gt($fin)) {
            return 0.0;
        }

        $feriados = Feriado::query()
            ->where('estado', true)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->with('gestion')
            ->get()
            ->filter(fn (Feriado $feriado) => $feriado->gestion !== null)
            ->map(fn (Feriado $feriado) => $feriado->fecha->toDateString())
            ->flip();

        $dias = 0;

        foreach (CarbonPeriod::create($inicio, $fin) as $fecha) {
            if ($fecha->isWeekend()) {
                continue;
            }

            if ($feriados->has($fecha->toDateString())) {
                continue;
            }

            $dias++;
        }

        return (float) $dias;
    }

    /**
     * Descuenta días de vacación empezando por la gestión más antigua.
     */
    private function descontarDias(int $empleadoId, float $cantidad): void
    {
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
                $vacacion->decrement('dias_disponibles', $restante);
                $restante = 0;
            } else {
                $restante -= $vacacion->dias_disponibles;
                $vacacion->update(['dias_disponibles' => 0]);
            }
        }

        if ($restante > 0) {
            \Log::warning("El empleado #{$empleadoId} solicitó más días de los disponibles. Quedaron {$restante} días sin descontar.");
        }
    }

    /**
     * @return array<int, array{fecha:Carbon, gestion:int, dias:float, origen:string, anios:int}>
     */
    private function resolverCandidatosDelDia(Empleado $empleado, Carbon $fecha): array
    {
        $contrato = $empleado->contratoVigente;

        if (! $contrato?->fecha_inicio) {
            return [];
        }

        $candidatos = collect();

        $candidatoNormal = $this->construirCandidatoNormal($empleado, $fecha);
        if ($candidatoNormal !== null) {
            $candidatos->push($candidatoNormal);
        }

        $candidatoReconocido = $this->construirCandidatoReconocido($empleado, $fecha);
        if ($candidatoReconocido !== null) {
            $candidatos->push($candidatoReconocido);
        }

        $candidatoProteccion = $this->construirCandidatoProteccion($empleado, $fecha);
        if ($candidatoProteccion !== null) {
            $candidatos->push($candidatoProteccion);
        }

        if ($candidatos->isEmpty()) {
            return [];
        }

        return $candidatos
            ->groupBy('gestion')
            ->map(fn (Collection $grupo) => $grupo->sortByDesc('dias')->first())
            ->values()
            ->all();
    }

    /**
     * @return array{fecha:Carbon, gestion:int, dias:float, origen:string, anios:int}|null
     */
    private function construirCandidatoNormal(Empleado $empleado, Carbon $fecha): ?array
    {
        $contrato = $empleado->contratoVigente;
        $aniversario = $this->ajustarAniversarioAAnio($contrato->fecha_inicio, (int) $fecha->year);

        if (! $aniversario->isSameDay($fecha)) {
            return null;
        }

        return $this->crearCandidato($contrato->fecha_inicio->copy(), $aniversario, 'contrato');
    }

    /**
     * @return array{fecha:Carbon, gestion:int, dias:float, origen:string, anios:int}|null
     */
    private function construirCandidatoReconocido(Empleado $empleado, Carbon $fecha): ?array
    {
        $antiguedad = $empleado->antiguedadVigente;

        if (! $antiguedad) {
            return null;
        }

        $fechaReconocida = $this->obtenerFechaReconocidaBase($antiguedad);

        if (! $fechaReconocida) {
            return null;
        }

        $fechaReconocimiento = $antiguedad->created_at?->copy()->startOfDay() ?? $fecha->copy()->startOfDay();
        $aniversario = $this->ajustarAniversarioAAnio($fechaReconocida, (int) $fecha->year);

        if ($aniversario->lessThan($fechaReconocimiento)) {
            return null;
        }

        if (! $aniversario->isSameDay($fecha)) {
            return null;
        }

        return $this->crearCandidato($fechaReconocida, $aniversario, 'reconocimiento');
    }

    /**
     * @return array{fecha:Carbon, gestion:int, dias:float, origen:string, anios:int}|null
     */
    private function construirCandidatoProteccion(Empleado $empleado, Carbon $fecha): ?array
    {
        $contrato = $empleado->contratoVigente;
        $antiguedad = $empleado->antiguedadVigente;

        if (! $contrato?->fecha_inicio || ! $antiguedad) {
            return null;
        }

        $aniversarioNormal = $this->ajustarAniversarioAAnio($contrato->fecha_inicio, (int) $fecha->year);

        if (! $aniversarioNormal->isSameDay($fecha)) {
            return null;
        }

        $fechaReconocimiento = $antiguedad->created_at?->copy()->startOfDay();

        if (! $fechaReconocimiento || $fechaReconocimiento->greaterThan($fecha)) {
            return null;
        }

        $fechaReconocida = $this->obtenerFechaReconocidaBase($antiguedad);

        if (! $fechaReconocida) {
            return null;
        }

        $aniversarioReconocido = $this->obtenerPrimerAniversarioDisponible($fechaReconocida, $fechaReconocimiento);

        if (! $aniversarioReconocido->greaterThan($aniversarioNormal)) {
            return null;
        }

        return $this->crearCandidato($fechaReconocida, $aniversarioNormal, 'proteccion');
    }

    /**
     * @return array{fecha:Carbon, gestion:int, dias:float, origen:string, anios:int}|null
     */
    private function crearCandidato(CarbonInterface $inicioServicio, CarbonInterface $fechaConsolidacion, string $origen): ?array
    {
        $anios = (int) $inicioServicio->diffInYears($fechaConsolidacion);

        if ($anios <= 0) {
            return null;
        }

        $rango = Antiguedad::query()
            ->where('anios_desde', '<=', $anios)
            ->where('anios_hasta', '>=', $anios)
            ->first();

        if (! $rango) {
            return null;
        }

        return [
            'fecha' => $fechaConsolidacion->copy(),
            'gestion' => (int) $fechaConsolidacion->year,
            'dias' => (float) $rango->dias_asignados,
            'origen' => $origen,
            'anios' => $anios,
        ];
    }

    private function obtenerFechaReconocidaBase(EmpleadoAntiguedad $antiguedad): ?CarbonInterface
    {
        return $antiguedad->fecha_reconocida?->copy();
    }

    private function obtenerPrimerAniversarioDisponible(CarbonInterface $inicioServicio, CarbonInterface $fechaReferencia): Carbon
    {
        $aniversario = $this->ajustarAniversarioAAnio($inicioServicio, (int) $fechaReferencia->year);

        if ($aniversario->lessThan($fechaReferencia)) {
            $aniversario->addYear();
        }

        return $aniversario;
    }

    private function ajustarAniversarioAAnio(CarbonInterface $fechaBase, int $anio): Carbon
    {
        $mes = (int) $fechaBase->month;
        $dia = min((int) $fechaBase->day, Carbon::create($anio, $mes, 1)->endOfMonth()->day);

        return Carbon::create($anio, $mes, $dia)->startOfDay();
    }

    /**
     * @param  array{fecha:Carbon, gestion:int, dias:float, origen:string, anios:int}  $candidato
     * @return array{empleado:string, gestion:int, dias:float, origen:string, accion:string}|null
     */
    private function registrarVacacionAutomatica(Empleado $empleado, array $candidato): ?array
    {
        $gestion = Gestion::firstOrCreate(['anio' => $candidato['gestion']]);

        $vacacion = Vacacion::query()->firstOrNew([
            'empleado_id' => $empleado->id,
            'gestion_id' => $gestion->id,
        ]);

        $diasActuales = (float) ($vacacion->dias_disponibles ?? 0);

        if ($vacacion->exists && $diasActuales >= $candidato['dias']) {
            return null;
        }

        $vacacion->dias_disponibles = $candidato['dias'];
        $vacacion->save();

        return [
            'empleado' => $empleado->nombre_completo,
            'gestion' => $gestion->anio,
            'dias' => $candidato['dias'],
            'origen' => $candidato['origen'],
            'accion' => $vacacion->wasRecentlyCreated ? 'creada' : 'actualizada',
        ];
    }
}
