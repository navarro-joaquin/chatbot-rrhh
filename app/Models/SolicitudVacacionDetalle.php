<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudVacacionDetalle extends BaseModel
{
    protected $table = 'solicitud_vacacion_detalles';

    protected $fillable = [
        'solicitud_vacacion_id',
        'vacacion_id',
        'dias_descontados',
    ];

    protected function casts(): array
    {
        return [
            'dias_descontados' => 'decimal:1',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudVacacion::class, 'solicitud_vacacion_id');
    }

    public function vacacion(): BelongsTo
    {
        return $this->belongsTo(Vacacion::class);
    }
}
