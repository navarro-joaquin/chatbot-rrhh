<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpleadoAntiguedad extends BaseModel
{
    protected $table = 'empleado_antiguedades';

    protected $fillable = [
        'empleado_id',
        'contrato_id',
        'fecha_reconocida',
        'origen',
        'observaciones',
        'vigente',
    ];

    protected function casts(): array
    {
        return [
            'fecha_reconocida' => 'date',
            'vigente' => 'boolean',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(EmpleadoContrato::class, 'contrato_id');
    }
}
