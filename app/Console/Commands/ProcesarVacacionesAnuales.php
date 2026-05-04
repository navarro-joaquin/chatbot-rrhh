<?php

namespace App\Console\Commands;

use App\Services\VacacionService;
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
    protected $signature = 'app:procesar-vacaciones-anuales
        {--fecha= : Fecha a procesar en formato YYYY-MM-DD}
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
        $today = $this->option('fecha')
            ? Carbon::parse((string) $this->option('fecha'))->startOfDay()
            : Carbon::today();
        $debug = (bool) $this->option('debug');

        $this->info("Procesando vacaciones para el {$today->format('d/m/Y')}...");

        $resultados = $service->procesarVacacionesAutomaticas(
            $today,
            $debug ? function (string $mensaje, array $contexto = []): void {
                $detalle = empty($contexto)
                    ? ''
                    : ' '.json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $this->line("[debug] {$mensaje}{$detalle}");
            } : null
        );

        if (empty($resultados)) {
            $this->info('No hay consolidaciones de vacaciones para procesar hoy.');

            return;
        }

        foreach ($resultados as $resultado) {
            $mensaje = "{$resultado['accion']} vacación para {$resultado['empleado']} ".
                "en la gestión {$resultado['gestion']} con {$resultado['dias']} días ({$resultado['origen']}).";

            $this->info($mensaje);
            Log::info('Vacaciones automáticas procesadas.', $resultado);
        }

        $this->info('Proceso finalizado.');
    }
}
