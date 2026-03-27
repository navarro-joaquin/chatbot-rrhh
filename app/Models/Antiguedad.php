<?php

namespace App\Models;

class Antiguedad extends BaseModel
{
    protected $table = 'antiguedades';

    protected $fillable = [
        'anios_desde',
        'anios_hasta',
        'dias_asignados',
    ];
}
