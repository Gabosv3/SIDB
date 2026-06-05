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
            $table->enum('dia_semana', ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'])
                ->nullable()
                ->after('nombre')
                ->comment('Día de la semana asignado para esta ruta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rutas_cobro', function (Blueprint $table) {
            $table->dropColumn('dia_semana');
        });
    }
};
