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
     * Este servidor tiene un CHECK CONSTRAINT heredado del ENUM que ningún
     * DROP CHECK/CONSTRAINT (probados con nombre literal y buscando en
     * information_schema) logró quitar — parece un caso particular del
     * motor de esa base. En vez de pelear con el ALTER, se crea una columna
     * nueva, se copian los datos, y se elimina la columna vieja por
     * completo: al borrar la columna, cualquier constraint atado a ella
     * desaparece con ella sin importar cómo se llame.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('employee_profiles', function ($table) {
                $table->json('tipo_empleado')->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE `employee_profiles` ADD COLUMN `tipo_empleado_tmp` JSON NULL AFTER `tipo_empleado`');

        DB::statement("UPDATE `employee_profiles` SET `tipo_empleado_tmp` = JSON_ARRAY(`tipo_empleado`) WHERE `tipo_empleado` IS NOT NULL AND `tipo_empleado` <> ''");

        DB::statement('ALTER TABLE `employee_profiles` DROP COLUMN `tipo_empleado`');

        // CHANGE (no RENAME COLUMN) por compatibilidad con versiones viejas de
        // MySQL/MariaDB que no soportan la sintaxis RENAME COLUMN.
        DB::statement('ALTER TABLE `employee_profiles` CHANGE `tipo_empleado_tmp` `tipo_empleado` JSON NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('employee_profiles', function ($table) {
                $table->enum('tipo_empleado', ['vendedor', 'cobrador', 'administrador', 'supervisor', 'tecnico', 'otro'])->nullable()->change();
            });

            return;
        }

        DB::statement('ALTER TABLE `employee_profiles` ADD COLUMN `tipo_empleado_old` VARCHAR(50) NULL AFTER `tipo_empleado`');

        DB::statement("UPDATE `employee_profiles` SET `tipo_empleado_old` = JSON_UNQUOTE(JSON_EXTRACT(`tipo_empleado`, '$[0]')) WHERE `tipo_empleado` IS NOT NULL");

        DB::statement('ALTER TABLE `employee_profiles` DROP COLUMN `tipo_empleado`');

        DB::statement('ALTER TABLE `employee_profiles` CHANGE `tipo_empleado_old` `tipo_empleado` VARCHAR(50) NULL');
    }
};
