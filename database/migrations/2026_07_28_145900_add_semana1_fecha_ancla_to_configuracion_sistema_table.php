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
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            // Un lunes cualquiera que marca el inicio de la "semana 1" del ciclo
            // quincenal de rutas. A partir de esta fecha se alternan semana 1 y
            // semana 2 cada 7 días, hacia adelante y hacia atrás. Nula = el ciclo
            // no está configurado todavía y ninguna ruta se filtra por semana.
            $table->date('semana1_fecha_ancla')->nullable()->after('apk_notas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->dropColumn('semana1_fecha_ancla');
        });
    }
};
