<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compensacion extends BaseModel
{
    protected $table = 'compensaciones';

    protected $fillable = [
        'empleado_id',
        'gestion_id',
        'contrato_id',
        'cantidad_horas',
        'descripcion',
        'fecha_registro',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_horas' => 'decimal:4',
            'fecha_registro' => 'date',
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

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(EmpleadoContrato::class, 'contrato_id');
    }
}
