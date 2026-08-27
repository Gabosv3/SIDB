<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->enum('modalidad_pago', ['salario_fijo', 'comision', 'mixto'])->nullable()->after('salario_base');
            $table->decimal('porcentaje_comision', 5, 2)->nullable()->after('modalidad_pago');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn(['modalidad_pago', 'porcentaje_comision']);
        });
    }
};
