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
     * MySQL/MariaDB representa el ENUM original como un CHECK CONSTRAINT que
     * sigue activo aunque la columna cambie de tipo — hay que quitarlo antes
     * de convertir a JSON. En vez de adivinar el nombre, se busca en
     * information_schema y se dropean todos los que apliquen a esta columna.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('employee_profiles', function ($table) {
                $table->json('tipo_empleado')->nullable()->change();
            });

            return;
        }

        $this->eliminarChecksDe('tipo_empleado');

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

    /**
     * Busca en information_schema todos los CHECK CONSTRAINT de la tabla
     * employee_profiles cuya definición mencione la columna dada, y los
     * elimina uno por uno (probando la sintaxis de MySQL 8 y de MariaDB,
     * ya que difiere entre ambos motores).
     */
    private function eliminarChecksDe(string $columna): void
    {
        $database = DB::getDatabaseName();

        $checks = DB::select(
            'SELECT cc.CONSTRAINT_NAME AS nombre
             FROM information_schema.CHECK_CONSTRAINTS cc
             JOIN information_schema.TABLE_CONSTRAINTS tc
               ON tc.CONSTRAINT_NAME = cc.CONSTRAINT_NAME
              AND tc.TABLE_SCHEMA = cc.CONSTRAINT_SCHEMA
             WHERE cc.CONSTRAINT_SCHEMA = ?
               AND tc.TABLE_NAME = \'employee_profiles\'
               AND cc.CHECK_CLAUSE LIKE ?',
            [$database, '%'.$columna.'%']
        );

        foreach ($checks as $check) {
            $nombre = $check->nombre;

            try {
                DB::statement("ALTER TABLE `employee_profiles` DROP CHECK `{$nombre}`");
            } catch (\Throwable $e) {
                try {
                    DB::statement("ALTER TABLE `employee_profiles` DROP CONSTRAINT `{$nombre}`");
                } catch (\Throwable $e2) {
                    // Si ninguna de las dos sintaxis funciona, se sigue de todos
                    // modos — el MODIFY de abajo va a fallar con un error claro
                    // si el constraint de verdad seguía bloqueando el cambio.
                }
            }
        }
    }
};
