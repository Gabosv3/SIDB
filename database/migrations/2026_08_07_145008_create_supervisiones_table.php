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
        Schema::create('supervisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained('supervisores')->cascadeOnDelete();
            $table->foreignId('cobrador_id')->constrained('cobradores')->cascadeOnDelete();
            $table->foreignId('ruta_cobro_id')->constrained('rutas_cobro')->cascadeOnDelete();
            $table->date('fecha');
            $table->boolean('visito_clientes_correctos')->nullable();
            $table->boolean('efectivo_cuadrado')->nullable();
            $table->unsignedTinyInteger('calificacion'); // 1-5
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervisiones');
    }
};
