<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('tipo_sangre')->nullable()->after('estado_civil');
            $table->string('nacionalidad')->nullable()->after('municipio');
            $table->string('numero_afiliacion')->nullable()->after('nacionalidad');
            $table->string('contacto_emergencia_nombre')->nullable()->after('numero_afiliacion');
            $table->string('contacto_emergencia_telefono')->nullable()->after('contacto_emergencia_nombre');
            $table->decimal('meta_ventas_mensual', 10, 2)->nullable()->after('salario_base');
            $table->decimal('meta_cobros_mensual', 10, 2)->nullable()->after('meta_ventas_mensual');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_sangre', 'nacionalidad', 'numero_afiliacion',
                'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
                'meta_ventas_mensual', 'meta_cobros_mensual',
            ]);
        });
    }
};
