<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->enum('forma_pago_periodo', ['semanal', 'quincenal', 'mensual'])->nullable()->after('medio_pago');
            $table->string('lugar_entrega_herramientas')->nullable()->after('herramientas_material');
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn(['forma_pago_periodo', 'lugar_entrega_herramientas']);
        });
    }
};
