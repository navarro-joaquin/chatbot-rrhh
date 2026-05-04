<?php

namespace App\Services;

use App\Models\Antiguedad;
use App\Models\Empleado;
use App\Models\EmpleadoAntiguedad;
use App\Models\Feriado;
use App\Models\Gestion;
use App\Models\SolicitudVacacion;
use App\Models\SolicitudVacacionDetalle;
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

            $this->descontarDias($empleado->id, $diasARestar, $solicitud);

            return $solicitud;
        });
    }

    /**
     * Actualiza una solicitud existente reajustando el saldo de vacaciones.
     */
    public function actualizarSolicitud(SolicitudVacacion $solicitud, array $data): SolicitudVacacion
    {
        return DB::transaction(function () use ($solicitud, $data) {
            $solicitud->loadMissing('detalles.vacacion');

            $this->restaurarDiasDeSolicitud($solicitud);

            $empleado = Empleado::findOrFail($data['empleado_id']);
            $diasARestar = (float) $data['dias_solicitados'];

            $solicitud->update([
                'empleado_id' => $empleado->id,
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
                'dias_solicitados' => $diasARestar,
                'motivo' => $data['motivo'] ?? null,
            ]);

            $this->descontarDias($empleado->id, $diasARestar, $solicitud);

            return $solicitud->fresh(['detalles']);
        });
    }

    /**
     * Procesa vacaciones automáticas para la fecha indicada.
     *
     * @return array<int, array{empleado:string, gestion:int, dias:float, origen:string, accion:string}>
     */
    public function procesarVacacionesAutomaticas(Carbon $fecha, ?callable $debugger = null): array
    {
        $empleados = Empleado::query()
            ->with(['contratoVigente', 'antiguedadVigente'])
            ->where('estado', true)
            ->whereHas('contratoVigente', fn ($query) => $query->where('tipo', 'Planta'))
            ->get();

        $this->debug($debugger, 'Empleados elegibles cargados', [
            'fecha' => $fecha->toDateString(),
            'cantidad' => $empleados->count(),
        ]);

        return $this->procesarColeccionDeEmpleados($empleados, $fecha, $debugger);
    }

    /**
     * Procesa vacaciones automáticas para un solo empleado en la fecha indicada.
     *
     * @return array<int, array{empleado:string, gestion:int, dias:float, origen:string, accion:string}>
     */
    public function procesarVacacionesAutomaticasParaEmpleado(int $empleadoId, Carbon $fecha, ?callable $debugger = null): array
    {
        $empleado = Empleado::query()
            ->with(['contratoVigente', 'antiguedadVigente'])
            ->whereKey($empleadoId)
            ->where('estado', true)
            ->whereHas('contratoVigente', fn ($query) => $query->where('tipo', 'Planta'))
            ->first();

        if (! $empleado) {
            $this->debug($debugger, 'Empleado no elegible para consolidacion puntual', [
                'empleado_id' => $empleadoId,
                'fecha' => $fecha->toDateString(),
            ]);

            return [];
        }

        return $this->procesarColeccionDeEmpleados(collect([$empleado]), $fecha, $debugger);
    }

    /**
     * @param  Collection<int, Empleado>  $empleados
     * @return array<int, array{empleado:string, gestion:int, dias:float, origen:string, accion:string}>
     */
    private function procesarColeccionDeEmpleados(Collection $empleados, Carbon $fecha, ?callable $debugger = null): array
    {
        $resultados = [];

        foreach ($empleados as $empleado) {
            $this->debug($debugger, 'Evaluando empleado', [
                'empleado_id' => $empleado->id,
                'empleado' => $empleado->nombre_completo,
                'contrato_id' => $empleado->contratoVigente?->id,
                'contrato_tipo' => $empleado->contratoVigente?->tipo,
                'contrato_fecha_inicio' => $empleado->contratoVigente?->fecha_inicio?->toDateString(),
                'contrato_vigente' => $empleado->contratoVigente?->es_vigente,
                'antiguedad_id' => $empleado->antiguedadVigente?->id,
                'fecha_reconocida' => $empleado->antiguedadVigente?->fecha_reconocida?->toDateString(),
                'vigencia_desde' => $empleado->antiguedadVigente?->vigencia_desde?->toDateString(),
                'fecha_registro_reconocimiento' => $empleado->antiguedadVigente?->created_at?->toDateTimeString(),
            ]);

            $candidatos = $this->resolverCandidatosDelDia($empleado, $fecha, $debugger);

            if (empty($candidatos)) {
                $this->debug($debugger, 'Empleado sin candidatos para la fecha', [
                    'empleado_id' => $empleado->id,
                    'fecha' => $fecha->toDateString(),
                ]);
            }

            foreach ($candidatos as $candidato) {
                $this->debug($debugger, 'Candidato generado', [
                    'empleado_id' => $empleado->id,
                    'gestion' => $candidato['gestion'],
                    'dias' => $candidato['dias'],
                    'origen' => $candidato['origen'],
                    'anios' => $candidato['anios'],
                ]);

                $resultado = $this->registrarVacacionAutomatica($empleado, $candidato, $debugger);

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
    private function descontarDias(int $empleadoId, float $cantidad, ?SolicitudVacacion $solicitud = null): void
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

            $diasDisponibles = (float) $vacacion->dias_disponibles;
            $descuento = min($diasDisponibles, $restante);

            if ($descuento <= 0) {
                continue;
            }

            if ($diasDisponibles >= $restante) {
                $vacacion->decrement('dias_disponibles', $restante);
                $restante = 0;
            } else {
                $restante -= $diasDisponibles;
                $vacacion->update(['dias_disponibles' => 0]);
            }

            if ($solicitud) {
                SolicitudVacacionDetalle::create([
                    'solicitud_vacacion_id' => $solicitud->id,
                    'vacacion_id' => $vacacion->id,
                    'dias_descontados' => $descuento,
                ]);
            }
        }

        if ($restante > 0) {
            \Log::warning("El empleado #{$empleadoId} solicitó más días de los disponibles. Quedaron {$restante} días sin descontar.");
        }
    }

    /**
     * Restaura los dias descontados por una solicitud antes de recalcularla.
     */
    private function restaurarDiasDeSolicitud(SolicitudVacacion $solicitud): void
    {
        if ($solicitud->detalles->isNotEmpty()) {
            foreach ($solicitud->detalles as $detalle) {
                $detalle->vacacion?->increment('dias_disponibles', (float) $detalle->dias_descontados);
            }

            $solicitud->detalles()->delete();

            return;
        }

        $this->restaurarDiasLegacy($solicitud);
    }

    /**
     * Fallback para solicitudes anteriores a la bitacora de descuentos.
     */
    private function restaurarDiasLegacy(SolicitudVacacion $solicitud): void
    {
        $vacacion = Vacacion::query()
            ->where('empleado_id', $solicitud->empleado_id)
            ->join('gestiones', 'vacaciones.gestion_id', '=', 'gestiones.id')
            ->orderBy('gestiones.anio', 'asc')
            ->select('vacaciones.*')
            ->first();

        if (! $vacacion) {
            return;
        }

        $vacacion->increment('dias_disponibles', (float) $solicitud->dias_solicitados);

        \Log::warning('Solicitud de vacacion restaurada sin detalle historico.', [
            'solicitud_id' => $solicitud->id,
            'empleado_id' => $solicitud->empleado_id,
        ]);
    }

    /**
     * @return array<int, array{fecha:Carbon, gestion:int, dias:float, origen:string, anios:int}>
     */
    private function resolverCandidatosDelDia(Empleado $empleado, Carbon $fecha, ?callable $debugger = null): array
    {
        $contrato = $empleado->contratoVigente;

        if (! $contrato?->fecha_inicio) {
            $this->debug($debugger, 'Empleado descartado sin contrato vigente con fecha de inicio', [
                'empleado_id' => $empleado->id,
            ]);

            return [];
        }

        $candidatos = collect();

        $candidatoNormal = $this->construirCandidatoNormal($empleado, $fecha, $debugger);
        if ($candidatoNormal !== null) {
            $candidatos->push($candidatoNormal);
        }

        $candidatoReconocido = $this->construirCandidatoReconocido($empleado, $fecha, $debugger);
        if ($candidatoReconocido !== null) {
            $candidatos->push($candidatoReconocido);
        }

        $candidatoProteccion = $this->construirCandidatoProteccion($empleado, $fecha, $debugger);
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
    private function construirCandidatoNormal(Empleado $empleado, Carbon $fecha, ?callable $debugger = null): ?array
    {
        $contrato = $empleado->contratoVigente;
        $aniversario = $this->ajustarAniversarioAAnio($contrato->fecha_inicio, (int) $fecha->year);

        if (! $aniversario->isSameDay($fecha)) {
            $this->debug($debugger, 'Contrato descartado por aniversario distinto', [
                'empleado_id' => $empleado->id,
                'fecha_evaluada' => $fecha->toDateString(),
                'aniversario_contrato' => $aniversario->toDateString(),
            ]);

            return null;
        }

        return $this->crearCandidato($contrato->fecha_inicio->copy(), $aniversario, 'contrato');
    }

    /**
     * @return array{fecha:Carbon, gestion:int, dias:float, origen:string, anios:int}|null
     */
    private function construirCandidatoReconocido(Empleado $empleado, Carbon $fecha, ?callable $debugger = null): ?array
    {
        $antiguedad = $empleado->antiguedadVigente;

        if (! $antiguedad) {
            $this->debug($debugger, 'Reconocimiento descartado sin antiguedad vigente', [
                'empleado_id' => $empleado->id,
            ]);

            return null;
        }

        $fechaReconocida = $this->obtenerFechaReconocidaBase($antiguedad);

        if (! $fechaReconocida) {
            $this->debug($debugger, 'Reconocimiento descartado sin fecha reconocida', [
                'empleado_id' => $empleado->id,
                'antiguedad_id' => $antiguedad->id,
            ]);

            return null;
        }

        $fechaReconocimiento = $this->obtenerFechaVigenciaReconocimiento($antiguedad, $fecha);
        $aniversario = $this->ajustarAniversarioAAnio($fechaReconocida, (int) $fecha->year);

        if ($aniversario->lessThan($fechaReconocimiento)) {
            $this->debug($debugger, 'Reconocimiento descartado por ser previo a la fecha de registro', [
                'empleado_id' => $empleado->id,
                'fecha_evaluada' => $fecha->toDateString(),
                'aniversario_reconocido' => $aniversario->toDateString(),
                'fecha_registro_reconocimiento' => $fechaReconocimiento->toDateString(),
            ]);

            return null;
        }

        if (! $aniversario->isSameDay($fecha)) {
            $this->debug($debugger, 'Reconocimiento descartado por aniversario distinto', [
                'empleado_id' => $empleado->id,
                'fecha_evaluada' => $fecha->toDateString(),
                'aniversario_reconocido' => $aniversario->toDateString(),
            ]);

            return null;
        }

        return $this->crearCandidato($fechaReconocida, $aniversario, 'reconocimiento');
    }

    /**
     * @return array{fecha:Carbon, gestion:int, dias:float, origen:string, anios:int}|null
     */
    private function construirCandidatoProteccion(Empleado $empleado, Carbon $fecha, ?callable $debugger = null): ?array
    {
        $contrato = $empleado->contratoVigente;
        $antiguedad = $empleado->antiguedadVigente;

        if (! $contrato?->fecha_inicio || ! $antiguedad) {
            $this->debug($debugger, 'Proteccion descartada por falta de contrato o antiguedad vigente', [
                'empleado_id' => $empleado->id,
            ]);

            return null;
        }

        $fechaReconocimiento = $this->obtenerFechaVigenciaReconocimiento($antiguedad, $fecha);

        if (! $fechaReconocimiento || ! $fechaReconocimiento->isSameDay($fecha)) {
            $this->debug($debugger, 'Proteccion descartada porque no es la fecha de reconocimiento vigente', [
                'empleado_id' => $empleado->id,
                'fecha_evaluada' => $fecha->toDateString(),
                'fecha_registro_reconocimiento' => $fechaReconocimiento?->toDateString(),
            ]);

            return null;
        }

        $aniversarioNormal = $this->ajustarAniversarioAAnio($contrato->fecha_inicio, (int) $fecha->year);

        $fechaReconocida = $this->obtenerFechaReconocidaBase($antiguedad);

        if (! $fechaReconocida) {
            $this->debug($debugger, 'Proteccion descartada sin fecha reconocida', [
                'empleado_id' => $empleado->id,
            ]);

            return null;
        }

        $aniversarioReconocido = $this->obtenerPrimerAniversarioDisponible($fechaReconocida, $fechaReconocimiento);

        if ($aniversarioNormal->lessThan($fechaReconocimiento)) {
            $this->debug($debugger, 'Proteccion descartada porque la gestion actual ya consolido por contrato', [
                'empleado_id' => $empleado->id,
                'fecha_evaluada' => $fecha->toDateString(),
                'aniversario_contrato' => $aniversarioNormal->toDateString(),
                'fecha_registro_reconocimiento' => $fechaReconocimiento->toDateString(),
            ]);

            return null;
        }

        if (! $aniversarioReconocido->greaterThan($aniversarioNormal)) {
            $this->debug($debugger, 'Proteccion descartada porque el reconocimiento no desplaza la siguiente consolidacion', [
                'empleado_id' => $empleado->id,
                'aniversario_reconocido' => $aniversarioReconocido->toDateString(),
                'aniversario_contrato' => $aniversarioNormal->toDateString(),
            ]);

            return null;
        }

        return $this->crearCandidato($fechaReconocida, $fechaReconocimiento, 'proteccion');
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

    private function obtenerFechaVigenciaReconocimiento(EmpleadoAntiguedad $antiguedad, Carbon $fechaPorDefecto): CarbonInterface
    {
        return $antiguedad->vigencia_desde?->copy()->startOfDay()
            ?? $antiguedad->created_at?->copy()->startOfDay()
            ?? $fechaPorDefecto->copy()->startOfDay();
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
    private function registrarVacacionAutomatica(Empleado $empleado, array $candidato, ?callable $debugger = null): ?array
    {
        $gestion = Gestion::firstOrCreate(['anio' => $candidato['gestion']]);

        $vacacion = Vacacion::query()->firstOrNew([
            'empleado_id' => $empleado->id,
            'gestion_id' => $gestion->id,
        ]);

        $diasActuales = (float) ($vacacion->dias_disponibles ?? 0);

        if ($vacacion->exists && $diasActuales >= $candidato['dias']) {
            $this->debug($debugger, 'Vacacion omitida porque ya existe una igual o mejor', [
                'empleado_id' => $empleado->id,
                'gestion' => $gestion->anio,
                'dias_actuales' => $diasActuales,
                'dias_candidato' => $candidato['dias'],
            ]);

            return null;
        }

        $vacacion->dias_disponibles = $vacacion->exists
            ? $diasActuales + $candidato['dias']
            : $candidato['dias'];
        $vacacion->save();

        $this->debug($debugger, 'Vacacion persistida', [
            'empleado_id' => $empleado->id,
            'gestion' => $gestion->anio,
            'dias' => $candidato['dias'],
            'origen' => $candidato['origen'],
            'accion' => $vacacion->wasRecentlyCreated ? 'creada' : 'actualizada',
        ]);

        return [
            'empleado' => $empleado->nombre_completo,
            'gestion' => $gestion->anio,
            'dias' => $candidato['dias'],
            'origen' => $candidato['origen'],
            'accion' => $vacacion->wasRecentlyCreated ? 'creada' : 'actualizada',
        ];
    }

    private function debug(?callable $debugger, string $mensaje, array $contexto = []): void
    {
        if ($debugger === null) {
            return;
        }

        $debugger($mensaje, $contexto);
    }
}
