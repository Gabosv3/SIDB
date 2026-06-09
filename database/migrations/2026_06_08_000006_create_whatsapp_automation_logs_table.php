<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->nullable()->constrained('whatsapp_automations')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('cuota_id')->nullable()->constrained('gestion_cobros')->nullOnDelete();
            $table->foreignId('whatsapp_message_id')->nullable()->constrained('whatsapp_messages')->nullOnDelete();
            $table->string('tipo')->nullable();
            $table->enum('estado', ['pending', 'sent', 'failed', 'skipped'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();

            // Índice único para evitar duplicados: misma automatización + cliente + cuota + día
            $table->unique(['automation_id', 'cliente_id', 'cuota_id'], 'unique_automation_cliente_cuota');
            $table->index('estado');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_automation_logs');
    }
};
