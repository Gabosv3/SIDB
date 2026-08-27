<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            // Número de empleado que se le asigna al inscribir su huella/rostro
            // directamente en el equipo Hikvision -- es lo único que el equipo
            // manda en cada marcaje, así que es el dato que lo relaciona con este
            // perfil.
            $table->string('codigo_asistencia')->nullable()->unique()->after('codigo_empleado');
            // Hora esperada de entrada/salida, para poder calcular tardanzas de
            // forma exacta. horario_laboral (texto libre) sigue existiendo para el
            // contrato de trabajo, pero no sirve para comparar automáticamente.
            $table->time('hora_entrada_esperada')->nullable()->after('horario_laboral');
            $table->time('hora_salida_esperada')->nullable()->after('hora_entrada_esperada');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn(['codigo_asistencia', 'hora_entrada_esperada', 'hora_salida_esperada']);
        });
    }
};
