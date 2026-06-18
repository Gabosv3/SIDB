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
        Schema::create('anticipos_cobrador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobrador_id')->constrained('cobradores');
            $table->foreignId('autorizado_por')->constrained('users');
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->date('semana_inicio'); // lunes
            $table->date('semana_fin');    // domingo
            $table->string('descripcion')->nullable();
            $table->enum('estado', ['pendiente', 'descontado'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anticipos_cobrador');
    }
};
