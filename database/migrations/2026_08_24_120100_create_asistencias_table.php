<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            // Nullable: si el equipo manda un código de empleado que no coincide
            // con ningún perfil (mal inscrito, o inscrito antes de vincularlo
            // aquí), el marcaje igual se guarda para no perderlo -- solo que
            // queda "huérfano" hasta que alguien lo relacione a mano.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('codigo_empleado_dispositivo')->nullable();
            $table->enum('tipo', ['entrada', 'salida', 'desconocido'])->default('desconocido');
            $table->dateTime('fecha_hora');
            $table->string('metodo', 30)->nullable();
            $table->string('dispositivo', 100)->nullable();
            $table->json('payload_crudo')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
