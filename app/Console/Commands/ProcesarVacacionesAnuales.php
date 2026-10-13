<?php

namespace App\Console\Commands;

use App\Services\VacacionService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcesarVacacionesAnuales extends Command
{
    /**
     * Clave de caché con la última fecha procesada por la ejecución programada.
     */
    public const CACHE_ULTIMA_FECHA = 'vacaciones-anuales:ultima-fecha-procesada';

    /**
     * Máximo de días anteriores que se recuperan si el proceso dejó de ejecutarse.
     */
    public const MAX_DIAS_RECUPERACION = 366;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:procesar-vacaciones-anuales
        {--fecha= : Procesa solo esta fecha (YYYY-MM-DD), sin recuperar días pendientes}
        {--debug : Muestra el detalle de evaluacion por empleado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registra automáticamente los días de vacaciones anuales según contrato vigente y antigüedad reconocida.';

    /**
     * Execute the console command.
     */
    public function handle(VacacionService $service): void
    {
        $fechaManual = $this->option('fecha')
            ? Carbon::parse((string) $this->option('fecha'))->startOfDay()
            : null;
        $today = Carbon::today();
        $debug = (bool) $this->option('debug');

        $fechas = $fechaManual
            ? [$fechaManual]
            : CarbonPeriod::create($this->resolverFechaInicial($today), $today)->toArray();

        $resultados = [];

        foreach ($fechas as $fecha) {
            $this->info("Procesando vacaciones para el {$fecha->format('d/m/Y')}...");

            $resultadosDelDia = $service->procesarVacacionesAutomaticas(
                Carbon::instance($fecha),
                $debug ? function (string $mensaje, array $contexto = []): void {
                    $detalle = empty($contexto)
                        ? ''
                        : ' '.json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    $this->line("[debug] {$mensaje}{$detalle}");
                } : null
            );

            foreach ($resultadosDelDia as $resultado) {
                $mensaje = "{$resultado['accion']} vacación para {$resultado['empleado']} ".
                    "en la gestión {$resultado['gestion']} con {$resultado['dias']} días ({$resultado['origen']}).";

                $this->info($mensaje);
                Log::info('Vacaciones automáticas procesadas.', $resultado + ['fecha_proceso' => $fecha->toDateString()]);
            }

            $resultados = [...$resultados, ...$resultadosDelDia];
        }

        if (! $fechaManual) {
            Cache::forever(self::CACHE_ULTIMA_FECHA, $today->toDateString());
        }

        if (empty($resultados)) {
            $this->info('No hay consolidaciones de vacaciones para procesar hoy.');

            return;
        }

        $this->info('Proceso finalizado.');
    }

    /**
     * Devuelve el día siguiente a la última ejecución registrada, para recuperar
     * los aniversarios de los días en que el proceso no llegó a ejecutarse.
     */
    private function resolverFechaInicial(Carbon $today): Carbon
    {
        $ultimaFecha = Cache::get(self::CACHE_ULTIMA_FECHA);

        if (! is_string($ultimaFecha)) {
            return $today->copy();
        }

        $desde = Carbon::parse($ultimaFecha)->startOfDay()->addDay();

        if ($desde->greaterThan($today)) {
            return $today->copy();
        }

        $limite = $today->copy()->subDays(self::MAX_DIAS_RECUPERACION);

        if ($desde->lessThan($limite)) {
            $this->warn("La última ejecución fue el {$ultimaFecha}; solo se recuperan los últimos ".self::MAX_DIAS_RECUPERACION.' días.');
            Log::warning('Recuperación de vacaciones anuales truncada.', ['ultima_fecha' => $ultimaFecha]);

            return $limite;
        }

        if ($desde->lessThan($today)) {
            $this->warn("Recuperando días sin procesar desde el {$desde->format('d/m/Y')}.");
        }

        return $desde;
    }
}
