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
        Schema::create('encuestas_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained('supervisores')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('cobrador_id')->nullable()->constrained('cobradores')->nullOnDelete();
            $table->date('fecha');

            // Lo que dice el cliente (respuestas de la encuesta)
            $table->text('monto_frecuencia_pago')->nullable(); // pregunta 1: "cuánto paga y cada cuánto"
            $table->string('cobrador_reportado_cliente')->nullable(); // pregunta 2: a quién dice que le entregó el dinero
            $table->boolean('recibio_comprobante')->nullable();
            $table->decimal('ultimo_pago_monto_cliente', 10, 2)->nullable(); // pregunta 3
            $table->date('ultimo_pago_fecha_cliente')->nullable();
            $table->decimal('saldo_informado_cliente', 10, 2)->nullable();

            // Verificación interna — se completa automáticamente con lo que
            // el sistema realmente tiene registrado al momento de guardar.
            $table->decimal('pago_registrado_bm', 10, 2)->nullable();
            $table->decimal('saldo_registrado_bm', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();

            $table->enum('resultado', [
                'coincide',
                'diferencia_investigar',
                'pago_no_registrado',
                'comprobante_inconsistente',
            ]);
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encuestas_cliente');
    }
};
