<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Gestion extends BaseModel
{
    protected $table = 'gestiones';

    protected $fillable = [
        'anio',
    ];

    public function vacaciones(): HasMany
    {
        return $this->hasMany(Vacacion::class);
    }

    public function compensaciones(): HasMany
    {
        return $this->hasMany(Compensacion::class);
    }

    public function feriados(): HasMany
    {
        return $this->hasMany(Feriado::class);
    }

    public function consolidacionesVacaciones(): HasMany
    {
        return $this->hasMany(ConsolidacionVacacion::class);
    }
}
