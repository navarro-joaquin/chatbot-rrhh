<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudCompensacion extends BaseModel
{
    /** @use HasFactory<\Database\Factories\SolicitudCompensacionFactory> */
    use HasFactory;

    protected $table = 'solicitudes_compensaciones';

    protected $fillable = [
        'empleado_id',
        'fecha_compensacion',
        'horas_solicitadas',
        'motivo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_compensacion' => 'date',
            'horas_solicitadas' => 'decimal:2',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
