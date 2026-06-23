<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class EmployeeProfile extends Model
{
    use LogsActivity;

    protected $table = 'employee_profiles';

    protected $fillable = [
        'user_id', 'foto', 'dui', 'nit', 'fecha_nacimiento', 'genero', 'estado_civil', 'tipo_sangre',
        'telefono_whatsapp', 'direccion', 'departamento', 'municipio', 'nacionalidad', 'numero_afiliacion',
        'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
        'codigo_empleado', 'cargo', 'tipo_empleado', 'fecha_ingreso', 'fecha_salida',
        'salario_base', 'meta_ventas_mensual', 'meta_cobros_mensual', 'tipo_contrato', 'horario_laboral', 'estado_laboral',
        'supervisor_id', 'puede_usar_pos_movil',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
        'fecha_salida' => 'date',
        'salario_base' => 'decimal:2',
        'meta_ventas_mensual' => 'decimal:2',
        'meta_cobros_mensual' => 'decimal:2',
        'puede_usar_pos_movil' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmployeeProfile $perfil): void {
            if (empty($perfil->codigo_empleado)) {
                $ultimo = static::max('id') ?? 0;
                $perfil->codigo_empleado = 'EMP-'.str_pad($ultimo + 1, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Perfil de empleado {$eventName}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function getEdadAttribute(): ?int
    {
        if (! $this->fecha_nacimiento) {
            return null;
        }

        return Carbon::parse($this->fecha_nacimiento)->age;
    }
}
