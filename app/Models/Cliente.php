<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'nombre_conyuge',
        'dui',
        'dui_foto_frente',
        'dui_foto_reverso',
        'nit',
        'telefono',
        'email',
        'direccion',
        'limite_credito',
        'saldo',
        'activo',
        // Referencias familiares
        'ref_fam1_nombre',
        'ref_fam1_telefono',
        'ref_fam1_parentesco',
        'ref_fam2_nombre',
        'ref_fam2_telefono',
        'ref_fam2_parentesco',
        // Referencias conocidas
        'ref_con1_nombre',
        'ref_con1_telefono',
        'ref_con1_trabajo',
        'ref_con2_nombre',
        'ref_con2_telefono',
        'ref_con2_trabajo',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'saldo'          => 'decimal:2',
        'activo'         => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function cuentasCobrar(): HasMany
    {
        return $this->hasMany(CuentaCobrar::class, 'cliente_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
