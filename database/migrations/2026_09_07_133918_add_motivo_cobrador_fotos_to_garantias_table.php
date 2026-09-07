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
        Schema::table('garantias', function (Blueprint $table) {
            $table->string('motivo')->nullable()->after('descripcion')
                ->comment('Motivo corto (ej. "No enfría", "Golpe en transporte") — la descripción queda para el detalle');
            $table->foreignId('cobrador_id')->nullable()->after('asignado_a')
                ->constrained('cobradores')->nullOnDelete()
                ->comment('Cobrador de la ruta del cliente — quien debe recoger el producto');
            $table->json('fotos')->nullable()->after('resolucion')
                ->comment('Rutas de las fotos del producto dañado, tomadas al reportar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('garantias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cobrador_id');
            $table->dropColumn(['motivo', 'fotos']);
        });
    }
};
