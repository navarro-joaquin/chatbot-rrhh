<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudCompensacionDetalle extends BaseModel
{
    protected $table = 'solicitud_compensacion_detalles';

    protected $fillable = [
        'solicitud_compensacion_id',
        'compensacion_id',
        'horas_descontadas',
    ];

    protected function casts(): array
    {
        return [
            'horas_descontadas' => 'decimal:2',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCompensacion::class, 'solicitud_compensacion_id');
    }

    public function compensacion(): BelongsTo
    {
        return $this->belongsTo(Compensacion::class);
    }
}
