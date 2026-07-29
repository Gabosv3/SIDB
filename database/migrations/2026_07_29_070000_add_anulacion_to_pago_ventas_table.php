<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pago_ventas', function (Blueprint $table) {
            // Un pago anulado nunca se borra — queda el registro completo, solo
            // deja de contar en los saldos/cuotas de la venta. anulado_en marca
            // si está vigente (null) o anulado (fecha).
            $table->timestamp('anulado_en')->nullable()->after('observaciones');
            $table->foreignId('anulado_por')->nullable()->after('anulado_en')
                ->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion', 255)->nullable()->after('anulado_por');
        });
    }

    public function down(): void
    {
        Schema::table('pago_ventas', function (Blueprint $table) {
            $table->dropForeign(['anulado_por']);
            $table->dropColumn(['anulado_en', 'anulado_por', 'motivo_anulacion']);
        });
    }
};
