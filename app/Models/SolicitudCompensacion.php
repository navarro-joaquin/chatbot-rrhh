<?php

namespace App\Models;

use Database\Factories\SolicitudCompensacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudCompensacion extends BaseModel
{
    /** @use HasFactory<SolicitudCompensacionFactory> */
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
            'horas_solicitadas' => 'decimal:4',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(SolicitudCompensacionDetalle::class, 'solicitud_compensacion_id');
    }
}
