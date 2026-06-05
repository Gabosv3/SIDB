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
        Schema::create('gestion_cobros', function (Blueprint $table) {
            $table->id();

            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->cascadeOnDelete();

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnDelete();

            $table->integer('numero_cuota')->comment('Número de cuota (1, 2, 3...)');
            $table->integer('total_cuotas')->comment('Total de cuotas del plan');
            $table->decimal('monto_cuota', 12, 2)->comment('Monto a cobrar en esta cuota');
            $table->date('fecha_vencimiento')->comment('Fecha de vencimiento de la cuota');
            $table->enum('estado', ['pendiente', 'cobrado', 'vencido'])->default('pendiente');
            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Índices
            $table->index('cliente_id');
            $table->index('estado');
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestion_cobros');
    }
};
