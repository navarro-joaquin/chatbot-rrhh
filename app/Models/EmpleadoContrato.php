<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EmpleadoContrato extends BaseModel
{
    protected $table = 'empleado_contratos';

    protected $fillable = [
        'empleado_id',
        'tipo',
        'numero_contrato',
        'nro_item',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'es_vigente',
        'resolucion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'es_vigente' => 'boolean',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function compensaciones(): HasMany
    {
        return $this->hasMany(Compensacion::class, 'contrato_id');
    }

    public function antiguedades(): HasMany
    {
        return $this->hasMany(EmpleadoAntiguedad::class, 'contrato_id');
    }

    public function antiguedadVigente(): HasOne
    {
        return $this->hasOne(EmpleadoAntiguedad::class, 'contrato_id')->where('vigente', true);
    }
}
