<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mismo esquema que anticipos_cobrador, para la Liquidación Semanal de
        // Ventas (vendedores) -- se mantiene como tabla aparte en vez de un
        // "empleado_id" genérico porque un empleado puede ser vendedor y
        // cobrador a la vez, con anticipos que se descuentan de liquidaciones
        // distintas.
        Schema::create('anticipos_vendedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendedor_id')->constrained('vendedores');
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

    public function down(): void
    {
        Schema::dropIfExists('anticipos_vendedor');
    }
};
