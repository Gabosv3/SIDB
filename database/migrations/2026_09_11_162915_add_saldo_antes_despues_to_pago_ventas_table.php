<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pago_ventas', function (Blueprint $table) {
            // Saldo de la venta antes/después de este ticket completo (no por
            // cuota individual) — se guarda al momento del pago para que el
            // recibo (en vivo o reimpreso desde el historial) siempre pueda
            // mostrar "Debía" y "Resta" reales, sin tener que reconstruirlos
            // después a partir del saldo actual (que ya pudo cambiar).
            $table->decimal('saldo_antes', 10, 2)->nullable()->after('monto');
            $table->decimal('saldo_despues', 10, 2)->nullable()->after('saldo_antes');
        });
    }

    public function down(): void
    {
        Schema::table('pago_ventas', function (Blueprint $table) {
            $table->dropColumn(['saldo_antes', 'saldo_despues']);
        });
    }
};
