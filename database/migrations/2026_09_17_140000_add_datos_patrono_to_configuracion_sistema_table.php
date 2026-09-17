<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Datos legales del patrono (dueño/representante) para rellenar
        // automáticamente el Contrato Individual de Trabajo en el formato
        // que exige el abogado -- son fijos para toda la empresa, se llenan
        // una sola vez desde Personalización.
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->string('patrono_nombre')->nullable();
            $table->enum('patrono_sexo', ['masculino', 'femenino'])->nullable();
            $table->date('patrono_fecha_nacimiento')->nullable();
            $table->string('patrono_profesion')->nullable();
            $table->enum('patrono_estado_civil', ['soltero', 'casado', 'divorciado', 'viudo', 'acompanado'])->nullable();
            $table->string('patrono_domicilio')->nullable();
            $table->string('patrono_residencia')->nullable();
            $table->string('patrono_nacionalidad')->nullable();
            $table->string('patrono_dui')->nullable();
            $table->string('patrono_dui_lugar_expedicion')->nullable();
            $table->date('patrono_dui_fecha_expedicion')->nullable();
            $table->string('patrono_razon_social')->nullable();
            $table->string('patrono_nit')->nullable();
            $table->string('patrono_actividad_economica')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->dropColumn([
                'patrono_nombre', 'patrono_sexo', 'patrono_fecha_nacimiento', 'patrono_profesion',
                'patrono_estado_civil', 'patrono_domicilio', 'patrono_residencia', 'patrono_nacionalidad',
                'patrono_dui', 'patrono_dui_lugar_expedicion', 'patrono_dui_fecha_expedicion',
                'patrono_razon_social', 'patrono_nit', 'patrono_actividad_economica',
            ]);
        });
    }
};
