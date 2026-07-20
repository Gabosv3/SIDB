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
        Schema::table('reintegros', function (Blueprint $table) {
            // Ruta de la que se sacó al cliente al mandarlo a recoger, para poder
            // devolverlo ahí cuando se decida mandarlo de nuevo a cobro normal.
            $table->foreignId('ruta_cobro_id_original')->nullable()
                ->after('cliente_id')
                ->constrained('rutas_cobro')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reintegros', function (Blueprint $table) {
            $table->dropForeign(['ruta_cobro_id_original']);
            $table->dropColumn('ruta_cobro_id_original');
        });
    }
};
