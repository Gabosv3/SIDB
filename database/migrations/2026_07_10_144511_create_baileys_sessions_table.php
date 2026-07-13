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
        Schema::create('baileys_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('session_key')->unique()->comment('Identificador usado por el servidor Node, ej. "user_5"');
            $table->enum('estado', ['desconectado', 'esperando_qr', 'conectado'])->default('desconectado');
            $table->string('numero_whatsapp')->nullable();
            $table->string('nombre_whatsapp')->nullable();
            $table->timestamp('conectado_at')->nullable();
            $table->timestamp('ultima_actividad_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baileys_sessions');
    }
};
