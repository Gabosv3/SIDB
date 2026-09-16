<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboComponente extends Model
{
    protected $table = 'combo_componentes';

    protected $fillable = [
        'producto_combo_id',
        'producto_componente_id',
        'cantidad',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        // Cualquier cambio en la composición de un combo (agregar/quitar
        // componente, cambiar cantidad) puede cambiar cuántos combos
        // completos alcanzan -- se recalcula el stock del combo al toque.
        static::saved(fn (ComboComponente $c) => Producto::recalcularStockCombo($c->producto_combo_id));
        static::deleted(fn (ComboComponente $c) => Producto::recalcularStockCombo($c->producto_combo_id));
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_combo_id');
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_componente_id');
    }
}
