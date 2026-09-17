<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enlaza el anticipo con la venta que lo originó cuando viene de
        // "Confirmar prima" en Resumen de Ventas del Día, para no permitir
        // confirmar la misma venta dos veces.
        Schema::table('anticipos_vendedor', function (Blueprint $table) {
            $table->foreignId('venta_id')->nullable()->unique()->constrained('ventas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('anticipos_vendedor', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venta_id');
        });
    }
};
