<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappConversacion extends Model
{
    protected $table = 'whatsapp_conversaciones';

    protected $fillable = [
        'empleado_id',
        'step'
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
