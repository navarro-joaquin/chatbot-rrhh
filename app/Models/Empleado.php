<?php

namespace App\Models;

use Database\Factories\EmpleadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empleado extends BaseModel
{
    /** @use HasFactory<EmpleadoFactory> */
    use HasFactory;

    protected $table = 'empleados';

    protected $fillable = [
        'nombre_completo',
        'carnet_identidad',
        'telefono',
        'correo_electronico',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function vacaciones(): HasMany
    {
        return $this->hasMany(Vacacion::class);
    }

    public function compensaciones(): HasMany
    {
        return $this->hasMany(Compensacion::class);
    }

    public function solicitudesVacaciones(): HasMany
    {
        return $this->hasMany(SolicitudVacacion::class);
    }

    public function solicitudesCompensaciones(): HasMany
    {
        return $this->hasMany(SolicitudCompensacion::class);
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(EmpleadoContrato::class);
    }

    public function contratoVigente(): HasOne
    {
        return $this->hasOne(EmpleadoContrato::class)->where('es_vigente', true);
    }

    public function antiguedades(): HasMany
    {
        return $this->hasMany(EmpleadoAntiguedad::class);
    }

    public function antiguedadVigente(): HasOne
    {
        return $this->hasOne(EmpleadoAntiguedad::class)->where('vigente', true);
    }

    public function whatsappConversacion(): HasOne
    {
        return $this->hasOne(WhatsappConversacion::class);
    }

    public function consolidacionesVacaciones(): HasMany
    {
        return $this->hasMany(ConsolidacionVacacion::class);
    }
}
