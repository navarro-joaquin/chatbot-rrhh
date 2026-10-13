<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Última fecha procesada por cada proceso programado, para recuperar días sin ejecutar.
 */
class EjecucionProceso extends Model
{
    protected $table = 'ejecucion_procesos';

    protected $fillable = [
        'proceso',
        'ultima_fecha',
    ];

    protected function casts(): array
    {
        return [
            'ultima_fecha' => 'date',
        ];
    }

    public static function ultimaFecha(string $proceso): ?CarbonInterface
    {
        return static::query()->firstWhere('proceso', $proceso)?->ultima_fecha;
    }

    public static function registrar(string $proceso, CarbonInterface $fecha): void
    {
        static::query()->updateOrCreate(
            ['proceso' => $proceso],
            ['ultima_fecha' => $fecha->toDateString()],
        );
    }
}
