<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actas_disciplinarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->comment('Empleado al que se le levanta el acta');
            $table->foreignId('generado_por')->constrained('users')->comment('Quien registró el acta');
            $table->string('tipo')->default('falta_injustificada'); // por ahora solo este tipo, se deja abierto para futuros (atraso, incumplimiento, etc)
            $table->date('fecha_hecho')->comment('Fecha en que ocurrió la falta');
            $table->text('descripcion')->comment('Hechos: qué pasó, por qué se considera injustificada');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actas_disciplinarias');
    }
};
