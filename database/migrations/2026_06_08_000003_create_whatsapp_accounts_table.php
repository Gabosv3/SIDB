<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            // Relación con usuario del sistema (vendedor o cobrador)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Tipo de cuenta
            $table->enum('tipo', ['vendedor', 'cobrador', 'empresa'])->default('empresa');
            $table->string('nombre')->nullable()->comment('Nombre descriptivo ej: Número principal cobros');
            // Datos Meta Cloud API
            $table->string('meta_business_id')->nullable();
            $table->string('waba_id')->nullable()->comment('WhatsApp Business Account ID');
            $table->string('phone_number_id')->nullable()->comment('Phone Number ID de Meta');
            $table->string('display_phone_number')->nullable()->comment('Número visible, ej: +503 7777-1111');
            $table->string('verified_name')->nullable()->comment('Nombre verificado en Meta');
            // Token encriptado
            $table->text('access_token')->nullable()->comment('Token encriptado con encrypt()');
            $table->timestamp('token_expires_at')->nullable();
            // Estado
            $table->string('webhook_status')->nullable()->comment('verified, pending, error');
            $table->enum('estado', ['activo', 'inactivo', 'suspendido'])->default('activo');
            $table->boolean('is_default')->default(false)->comment('Número principal de la empresa');
            $table->timestamps();

            $table->index('tipo');
            $table->index('estado');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
