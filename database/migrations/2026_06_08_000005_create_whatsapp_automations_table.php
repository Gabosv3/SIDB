<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_automations', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('tipo', [
                'recordatorio_antes_vencimiento',
                'recordatorio_dia_vencimiento',
                'recordatorio_mora',
                'confirmacion_pago',
                'verificacion_cobro',
                'seguimiento_promesa_pago',
            ]);
            $table->foreignId('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('whatsapp_templates')->nullOnDelete();
            $table->integer('dias_antes')->nullable()->comment('Días antes del vencimiento');
            $table->integer('dias_despues')->nullable()->comment('Días después del vencimiento (mora)');
            $table->time('hora_envio')->default('08:00:00');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('tipo');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_automations');
    }
};
