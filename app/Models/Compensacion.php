<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compensacion extends Model
{
    protected $table = 'compensaciones';
    protected $fillable = [
        'empleado_id',
        'gestion_id',
        'cantidad_horas',
        'descripcion',
        'fecha_registro',
        'estado'
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }
}
