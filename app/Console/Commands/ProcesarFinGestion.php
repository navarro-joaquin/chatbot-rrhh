<?php

namespace App\Console\Commands;

use App\Models\Feriado;
use App\Models\Gestion;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcesarFinGestion extends Command
{
    protected $signature = 'app:procesar-fin-gestion';

    protected $description = 'Comando para procesar el fin de la gestión actual';

    public function handle(): void
    {
        $today = Carbon::today();

        $gestion = Gestion::where('anio', $today->format('Y'))->first();
        $this->info('Gestion: '.$gestion->anio);

        $feriados = Feriado::where('gestion_id', $gestion->id)->get();
        foreach ($feriados as $feriado) {
            $feriado->update(['estado' => false]);
        }

        $this->info('Feriados: '.$feriados->count());

        $this->info('Proceso finalizado');
    }
}
