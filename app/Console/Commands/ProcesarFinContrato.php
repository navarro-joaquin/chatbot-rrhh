<?php

namespace App\Console\Commands;

use App\Models\Compensacion;
use App\Models\EmpleadoContrato;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcesarFinContrato extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:procesar-fin-contrato';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finaliza contratos eventuales vencidos y marca sus compensaciones como vencidas.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $today = Carbon::today();
        $this->info("Procesando finalizaciones de contrato para el {$today->format('d/m/Y')}...");

        $contratos = EmpleadoContrato::query()
            ->with('empleado')
            ->where('tipo', 'Eventual')
            ->where('estado', 'Vigente')
            ->where('es_vigente', true)
            ->whereDate('fecha_fin', '<=', $today)
            ->whereHas('empleado', fn ($query) => $query->where('estado', true))
            ->get();

        if ($contratos->isEmpty()) {
            $this->info('No hay contratos eventuales vencidos para procesar.');

            return;
        }

        $procesados = 0;

        foreach ($contratos as $contrato) {
            DB::transaction(function () use ($contrato, &$procesados, $today): void {
                $compensacionesActualizadas = Compensacion::query()
                    ->where('contrato_id', $contrato->id)
                    ->where('estado', '!=', 'vencido')
                    ->update(['estado' => 'vencido']);

                $contrato->update([
                    'estado' => 'Finalizado',
                    'es_vigente' => false,
                ]);

                $procesados++;

                $this->info(
                    "Contrato #{$contrato->id} finalizado para {$contrato->empleado->nombre_completo}. ".
                    "Compensaciones vencidas: {$compensacionesActualizadas}."
                );

                Log::info('Fin de contrato procesado automáticamente.', [
                    'fecha_proceso' => $today->toDateString(),
                    'contrato_id' => $contrato->id,
                    'empleado_id' => $contrato->empleado_id,
                    'empleado' => $contrato->empleado->nombre_completo,
                    'tipo' => $contrato->tipo,
                    'fecha_fin' => $contrato->fecha_fin?->toDateString(),
                    'compensaciones_vencidas' => $compensacionesActualizadas,
                ]);
            });
        }

        $this->info("Proceso finalizado. Contratos procesados: {$procesados}.");
    }
}
