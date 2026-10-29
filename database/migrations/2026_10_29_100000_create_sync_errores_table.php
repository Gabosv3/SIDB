<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Operaciones de la app móvil que el servidor rechazó (o que no logró
        // subir) — la app las reporta aquí para que el panel de monitoreo las
        // detecte. Sin esto, una venta o un cobro rechazado solo se enteraba
        // quien tenía el teléfono en la mano.
        Schema::create('sync_errores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pos_device_id')->nullable()->constrained('pos_devices')->nullOnDelete();
            // Id que genera la app por cada error: evita duplicados si reintenta el envío.
            $table->string('client_id', 64);
            // rechazado = el servidor respondió 4xx a una operación guardada offline
            // atascado  = lleva mucho tiempo en la cola sin poder subirse
            $table->string('tipo', 20)->default('rechazado');
            $table->string('metodo', 10)->nullable();
            $table->string('endpoint', 255)->nullable();
            $table->string('etiqueta', 255)->nullable();   // ej. "Cobro Juan Pérez — REC-12-000005"
            $table->decimal('monto', 12, 2)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('motivo')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->timestamp('ocurrido_en')->nullable();   // hora del teléfono
            $table->timestamp('resuelto_en')->nullable();
            $table->foreignId('resuelto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'client_id']);
            $table->index('resuelto_en');
        });

        // Resumen de lo que cada teléfono tiene sin subir, reportado en el heartbeat.
        Schema::table('pos_devices', function (Blueprint $table) {
            $table->unsignedSmallInteger('pendientes_sync')->default(0)->after('ultimo_error');
            $table->unsignedInteger('pendiente_antiguo_min')->nullable()->after('pendientes_sync');
            $table->unsignedSmallInteger('rechazados_sync')->default(0)->after('pendiente_antiguo_min');
        });
    }

    public function down(): void
    {
        Schema::table('pos_devices', function (Blueprint $table) {
            $table->dropColumn(['pendientes_sync', 'pendiente_antiguo_min', 'rechazados_sync']);
        });

        Schema::dropIfExists('sync_errores');
    }
};
