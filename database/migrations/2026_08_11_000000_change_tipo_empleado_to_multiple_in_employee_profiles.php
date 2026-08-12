<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tipo_empleado pasa de ENUM (un solo valor) a JSON (varios a la vez) —
     * una misma persona puede ser vendedor y cobrador simultáneamente, como
     * ya soporta el resto del sistema (Vendedor::es_cobrador).
     */
    public function up(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->json('tipo_empleado')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->enum('tipo_empleado', ['vendedor', 'cobrador', 'administrador', 'supervisor', 'tecnico', 'otro'])->nullable()->change();
        });
    }
};
