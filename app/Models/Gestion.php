<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gestion extends Model
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
}
