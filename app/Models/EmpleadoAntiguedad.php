<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpleadoAntiguedad extends BaseModel
{
    protected $table = 'empleado_antiguedades';

    protected $fillable = [
        'empleado_id',
        'contrato_id',
        'fecha_base',
        'anios_reconocidos',
        'meses_reconocidos',
        'dias_reconocidos',
        'origen',
        'observaciones',
        'vigente',
    ];

    protected function casts(): array
    {
        return [
            'fecha_base' => 'date',
            'anios_reconocidos' => 'integer',
            'meses_reconocidos' => 'integer',
            'dias_reconocidos' => 'integer',
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
