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
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->string('numero_compra', 100)->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('restrict');
            $table->dateTime('fecha_compra');
            $table->date('fecha_entrega_estimada')->nullable();
            $table->date('fecha_entrega_real')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('impuesto_porcentaje', 5, 2)->default(0);
            $table->decimal('impuesto_monto', 12, 2)->default(0);
            $table->decimal('descuento_monto', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('saldo_pendiente', 12, 2)->default(0);
            $table->enum('forma_pago', ['efectivo', 'transferencia', 'cheque', 'tarjeta', 'credito'])->default('credito');
            $table->string('condicion_pago', 100)->default('contado');
            $table->integer('dias_credito')->default(0);
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['pendiente', 'recibida', 'completada', 'cancelada', 'devuelta'])->default('pendiente');
            $table->longText('observaciones')->nullable();
            $table->timestamps();

            $table->index('numero_compra');
            $table->index('proveedor_id');
            $table->index('fecha_compra');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
