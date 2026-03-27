<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends BaseModel
{
    /** @use HasFactory<\Database\Factories\EmpleadoFactory> */
    use HasFactory;

    protected $table = 'empleados';

    protected $fillable = [
        'nombre_completo',
        'carnet_identidad',
        'telefono',
        'correo_electronico',
        'nro_item',
        'tipo',
        'fecha_contratacion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_contratacion' => 'date',
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
}
