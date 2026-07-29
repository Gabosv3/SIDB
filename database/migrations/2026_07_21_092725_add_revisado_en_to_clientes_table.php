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
        Schema::table('clientes', function (Blueprint $table) {
            // Reemplaza el checklist "revisado" que antes vivía solo en localStorage
            // del navegador — ahora es compartido entre todos los que entren a
            // /clientes-ruta, no se pierde al cambiar de dispositivo o limpiar el navegador.
            $table->timestamp('revisado_en')->nullable()->after('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('revisado_en');
        });
    }
};
