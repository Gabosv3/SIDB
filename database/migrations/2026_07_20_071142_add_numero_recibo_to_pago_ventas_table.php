<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pago_ventas', function (Blueprint $table) {
            // Un solo abono puede repartirse entre varias cuotas (varios
            // PagoVenta) — todos comparten el mismo numero_recibo para poder
            // agruparlos como un solo movimiento en el historial. Nulo en pagos
            // registrados antes de este campo.
            $table->string('numero_recibo')->nullable()->after('user_id');
            $table->index('numero_recibo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pago_ventas', function (Blueprint $table) {
            $table->dropIndex(['numero_recibo']);
            $table->dropColumn('numero_recibo');
        });
    }
};
