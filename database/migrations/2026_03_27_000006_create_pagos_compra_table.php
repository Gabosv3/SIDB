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
        Schema::create('pagos_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->onDelete('cascade');
            $table->date('fecha_pago');
            $table->decimal('monto', 12, 2);
            $table->enum('forma_pago', ['efectivo', 'transferencia', 'cheque', 'tarjeta'])->default('transferencia');
            $table->string('referencia_pago', 150)->nullable();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('compra_id');
            $table->index('fecha_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_compra');
    }
};
