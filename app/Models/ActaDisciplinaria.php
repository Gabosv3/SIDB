<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ActaDisciplinaria extends Model
{
    use LogsActivity;

    protected $table = 'actas_disciplinarias';

    protected $fillable = [
        'user_id',
        'generado_por',
        'tipo',
        'fecha_hecho',
        'descripcion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_hecho' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Acta disciplinaria {$eventName}");
    }
}
