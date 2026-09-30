<?php

namespace App\Models;

use Database\Factories\SolicitudVacacionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudVacacion extends BaseModel
{
    /** @use HasFactory<SolicitudVacacionFactory> */
    use HasFactory;

    protected $table = 'solicitudes_vacaciones';

    protected $fillable = [
        'empleado_id',
        'fecha_inicio',
        'fecha_fin',
        'dias_solicitados',
        'motivo',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'dias_solicitados' => 'decimal:4',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(SolicitudVacacionDetalle::class, 'solicitud_vacacion_id');
    }
}
