<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->string('wa_message_id')->nullable()->comment('ID del mensaje devuelto por Meta Cloud API');
            $table->enum('direction', ['in', 'out'])->default('out')->comment('in=entrante, out=saliente');
            $table->enum('type', ['text', 'image', 'document', 'audio', 'template'])->default('text');
            $table->text('body')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->json('payload')->nullable()->comment('Payload completo de Meta para debug');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('wa_message_id');
            $table->index('status');
            $table->index('direction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
