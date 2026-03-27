<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->date('fecha_pago_limite')->nullable()->after('fecha_venta');
            $table->integer('dias_credito')->default(0)->after('fecha_pago_limite');
        });

        Schema::create('pago_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('monto', 12, 2);
            $table->date('fecha_pago');
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'cheque', 'deposito'])->default('efectivo');
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_ventas');
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['fecha_pago_limite', 'dias_credito']);
        });
    }
};