<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    protected $fillable = [
        'user_id',
        'codigo_empleado_dispositivo',
        'tipo',
        'fecha_hora',
        'metodo',
        'dispositivo',
        'payload_crudo',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'payload_crudo' => 'array',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
