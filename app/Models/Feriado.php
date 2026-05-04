<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feriado extends BaseModel
{
    protected $table = 'feriados';

    protected $fillable = [
        'nombre',
        'fecha',
        'gestion_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estado' => 'boolean',
        ];
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }
}
