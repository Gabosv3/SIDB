<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reintegros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('vendedor_id')->constrained('vendedores');
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('asignado_por')->constrained('users');
            $table->enum('estado', ['pendiente', 'en_proceso', 'recuperado', 'no_recuperado'])->default('pendiente');
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->date('fecha_asignacion');
            $table->date('fecha_recuperacion')->nullable();
            $table->decimal('monto_adeudado', 10, 2)->default(0);
            $table->integer('cuotas_vencidas')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reintegros');
    }
};
