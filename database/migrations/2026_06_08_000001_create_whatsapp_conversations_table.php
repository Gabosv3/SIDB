<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // cobrador/vendedor asignado
            $table->string('wa_id')->nullable()->comment('Número de WhatsApp del cliente (formato: 503XXXXXXXX)');
            $table->string('phone_number_id')->nullable()->comment('Phone Number ID de Meta Cloud API');
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->text('ultimo_mensaje')->nullable();
            $table->timestamp('ultimo_mensaje_at')->nullable();
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('estado');
            $table->index('wa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
