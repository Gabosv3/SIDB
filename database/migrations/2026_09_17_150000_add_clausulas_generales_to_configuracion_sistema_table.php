<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Texto libre que se agrega automáticamente a TODOS los Contratos
        // Individuales de Trabajo que se generen, después de las cláusulas
        // propias de cada empleado -- para algo que aplique a todos sin
        // tener que repetirlo empleado por empleado.
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->text('contrato_clausulas_generales')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->dropColumn('contrato_clausulas_generales');
        });
    }
};
