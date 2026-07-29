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
        Schema::table('rutas_cobro', function (Blueprint $table) {
            // 1 = solo semana 1 del ciclo quincenal, 2 = solo semana 2, NULL = se
            // muestra todas las semanas (valor de transición para rutas que aún
            // no se han clasificado, para no ocultarlas de golpe en el POS).
            $table->unsignedTinyInteger('semana_ciclo')->nullable()->after('dia_semana');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rutas_cobro', function (Blueprint $table) {
            $table->dropColumn('semana_ciclo');
        });
    }
};
