<?php

namespace App\Console\Commands;

use App\Models\Feriado;
use App\Models\Gestion;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcesarFinGestion extends Command
{
    protected $signature = 'app:procesar-fin-gestion
        {--anio= : Gestión a cerrar; por defecto la del año en curso}';

    protected $description = 'Comando para procesar el fin de la gestión actual';

    public function handle(): int
    {
        $anio = (int) ($this->option('anio') ?: Carbon::today()->year);

        $gestion = Gestion::firstWhere('anio', $anio);

        if (! $gestion) {
            $this->warn("No existe la gestión {$anio}; no hay nada que procesar.");

            return self::SUCCESS;
        }

        $this->info('Gestion: '.$gestion->anio);

        $feriados = Feriado::query()
            ->where('gestion_id', $gestion->id)
            ->where('estado', true)
            ->get();

        foreach ($feriados as $feriado) {
            $feriado->update(['estado' => false]);
        }

        $this->info('Feriados: '.$feriados->count());

        $this->info('Proceso finalizado');

        return self::SUCCESS;
    }
}
