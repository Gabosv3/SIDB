<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Datos adicionales del trabajador que exige el formato de Contrato
        // Individual de Trabajo del abogado y que no existían todavía.
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('profesion_oficio')->nullable()->after('cargo');
            $table->string('residencia')->nullable()->after('direccion');
            $table->string('dui_lugar_expedicion')->nullable()->after('dui');
            $table->date('dui_fecha_expedicion')->nullable()->after('dui_lugar_expedicion');
            $table->enum('medio_pago', ['efectivo', 'transferencia', 'cheque', 'deposito'])->nullable()->after('modalidad_pago');
            $table->text('herramientas_material')->nullable()->after('medio_pago');
            $table->text('personas_dependientes')->nullable()->after('herramientas_material');
            $table->text('otras_estipulaciones')->nullable()->after('personas_dependientes');

            $table->text('horario_laboral')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'profesion_oficio', 'residencia', 'dui_lugar_expedicion', 'dui_fecha_expedicion',
                'medio_pago', 'herramientas_material', 'personas_dependientes', 'otras_estipulaciones',
            ]);

            $table->string('horario_laboral')->nullable()->change();
        });
    }
};
