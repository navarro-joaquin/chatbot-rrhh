<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Antiguedad extends Model
{
    protected $table = 'antiguedades';
    protected $fillable = [
        'anios_desde',
        'anios_hasta',
        'dias_asignados'
    ];
}
