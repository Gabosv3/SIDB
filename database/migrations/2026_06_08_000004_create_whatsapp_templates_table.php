<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->nullOnDelete();
            $table->string('name')->comment('Nombre de la plantilla en Meta, ej: recordatorio_pago');
            $table->string('language')->default('es');
            $table->enum('category', ['UTILITY', 'MARKETING', 'AUTHENTICATION'])->default('UTILITY');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('body')->nullable()->comment('Texto con variables {{1}}, {{nombre}}, etc.');
            $table->json('components')->nullable()->comment('Componentes completos para Meta API');
            $table->timestamps();

            $table->index('name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
