<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baileys se descontinuó (integración no oficial de WhatsApp, nunca quedó
     * estable) — se reconstruirá con WhatsApp Coexistence (API oficial de Meta).
     * whatsapp_conversations/whatsapp_messages NO se tocan: son tablas
     * genéricas, reutilizables para la nueva integración.
     */
    public function up(): void
    {
        Schema::dropIfExists('baileys_sessions');
    }

    public function down(): void
    {
        Schema::create('baileys_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('session_key')->unique();
            $table->enum('estado', ['desconectado', 'esperando_qr', 'conectado'])->default('desconectado');
            $table->string('numero_whatsapp')->nullable();
            $table->string('nombre_whatsapp')->nullable();
            $table->timestamp('conectado_at')->nullable();
            $table->timestamp('ultima_actividad_at')->nullable();
            $table->timestamps();
        });
    }
};
