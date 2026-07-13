<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina lo que era exclusivo del sistema viejo de WhatsApp Cloud API (Meta),
     * ya reemplazado por Baileys. whatsapp_conversations/whatsapp_messages se quedan
     * (las sigue usando Baileys), solo se les quitan las columnas específicas de Meta.
     */
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_account_id']);
            $table->dropColumn(['whatsapp_account_id', 'phone_number_id']);
        });

        // Orden por dependencias de FK: primero los hijos, luego los padres.
        Schema::dropIfExists('whatsapp_automation_logs');
        Schema::dropIfExists('whatsapp_automations');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('whatsapp_accounts');
    }

    public function down(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo', ['vendedor', 'cobrador', 'empresa'])->default('empresa');
            $table->string('meta_business_id')->nullable();
            $table->string('waba_id')->nullable();
            $table->string('phone_number_id')->nullable();
            $table->text('access_token')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('whatsapp_automations', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('whatsapp_automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->nullable()->constrained('whatsapp_automations')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->foreignId('whatsapp_account_id')->nullable()->after('user_id')
                ->constrained('whatsapp_accounts')->nullOnDelete();
            $table->string('phone_number_id')->nullable();
        });
    }
};
