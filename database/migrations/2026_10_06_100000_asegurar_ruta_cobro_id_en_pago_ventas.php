<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PagoVenta guarda la ruta del cliente al momento del cobro (ruta_cobro_id),
     * pero la migración original (2026_07_31_155139_add_ruta_cobro_id_to_pago_ventas_table)
     * se perdió del repositorio en un "Revert" aunque el modelo siguió usando la
     * columna. En bases que ya la tienen (producción) esto no hace nada; en una
     * instalación nueva o en la base de pruebas la crea, para que registrar un
     * pago no falle.
     */
    public function up(): void
    {
        if (Schema::hasColumn('pago_ventas', 'ruta_cobro_id')) {
            return;
        }

        Schema::table('pago_ventas', function (Blueprint $table) {
            $table->foreignId('ruta_cobro_id')->nullable()->after('cliente_id')
                ->constrained('rutas_cobro')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // No se revierte: la columna pertenece a la migración original.
    }
};
