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
        Schema::create('garantias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->foreignId('reportado_por')->constrained('users')->comment('Cobrador que reportó el problema');
            $table->foreignId('asignado_a')->nullable()->constrained('users')->nullOnDelete()->comment('Técnico/encargado de resolverla');
            // A diferencia de un reintegro, una garantía NO saca al cliente de
            // su ruta de cobro — sigue pagando su cuota normalmente mientras
            // se revisa/repara/cambia el producto.
            $table->string('estado')->default('pendiente'); // pendiente, en_proceso, resuelta, rechazada
            $table->text('descripcion');
            $table->text('resolucion')->nullable();
            $table->date('fecha_reporte');
            $table->date('fecha_resolucion')->nullable();
            $table->timestamps();

            $table->index(['estado']);
            $table->index(['cliente_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garantias');
    }
};
