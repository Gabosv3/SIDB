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
            // Posición del cliente en el orden de visita de su ruta original,
            // para restaurarla junto con ruta_cobro_id_original al devolverlo.
            $table->unsignedInteger('orden_original')->nullable()->after('ruta_cobro_id_original');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reintegros', function (Blueprint $table) {
            $table->dropColumn('orden_original');
        });
    }
};
