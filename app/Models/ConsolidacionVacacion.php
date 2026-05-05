<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsolidacionVacacion extends BaseModel
{
    protected $table = 'consolidacion_vacaciones';

    protected $fillable = [
        'empleado_id',
        'gestion_id',
        'dias_anadidos',
        'dias_totales_despues',
        'origen',
        'accion',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'dias_anadidos' => 'decimal:1',
            'dias_totales_despues' => 'decimal:1',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }
}
