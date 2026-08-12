<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tipo_empleado pasa de ENUM (un solo valor) a JSON (varios a la vez) —
     * una misma persona puede ser vendedor y cobrador simultáneamente, como
     * ya soporta el resto del sistema (Vendedor::es_cobrador).
     *
     * MySQL/MariaDB representa el ENUM original como un CHECK CONSTRAINT
     * (nombrado como "tabla.columna" en algunos hostings) que sigue
     * activo aunque la columna cambie de tipo — hay que quitarlo antes de
     * convertir a JSON, si no el ALTER lo rechaza.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('employee_profiles', function ($table) {
                $table->json('tipo_empleado')->nullable()->change();
            });

            return;
        }

        try {
            DB::statement('ALTER TABLE `employee_profiles` DROP CONSTRAINT `employee_profiles.tipo_empleado`');
        } catch (\Throwable $e) {
            // No existe (nombre distinto, o ya se quitó antes) — seguimos igual.
        }

        try {
            DB::statement('ALTER TABLE `employee_profiles` DROP CHECK `employee_profiles.tipo_empleado`');
        } catch (\Throwable $e) {
            // MariaDB usa DROP CHECK en vez de DROP CONSTRAINT en algunas versiones.
        }

        DB::statement('ALTER TABLE `employee_profiles` MODIFY `tipo_empleado` JSON NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('employee_profiles', function ($table) {
                $table->enum('tipo_empleado', ['vendedor', 'cobrador', 'administrador', 'supervisor', 'tecnico', 'otro'])->nullable()->change();
            });

            return;
        }

        DB::statement("ALTER TABLE `employee_profiles` MODIFY `tipo_empleado` ENUM('vendedor', 'cobrador', 'administrador', 'supervisor', 'tecnico', 'otro') NULL");
    }
};
