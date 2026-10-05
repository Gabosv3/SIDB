<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuadres_caja', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->unsignedBigInteger('user_id');           // persona que entrega el efectivo
            $table->unsignedBigInteger('cuadrado_por')->nullable(); // quien hizo el cuadre
            // Foto de lo que el sistema esperaba al momento de cuadrar
            $table->decimal('cobros_efectivo', 12, 2)->default(0);
            $table->decimal('ventas_contado', 12, 2)->default(0);
            $table->decimal('gastos', 12, 2)->default(0);
            $table->decimal('esperado', 12, 2)->default(0);
            $table->decimal('recibido', 12, 2)->default(0);
            $table->decimal('diferencia', 12, 2)->default(0);  // recibido - esperado (+ sobrante / - faltante)
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->unique(['fecha', 'user_id']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuadres_caja');
    }
};
