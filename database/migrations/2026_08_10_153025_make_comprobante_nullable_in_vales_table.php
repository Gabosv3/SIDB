<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sin doctrine/dbal instalado, ->change() no está disponible — se usa
        // SQL directo para quitar el NOT NULL. Los vales de consumo ya no
        // llevan foto (se aprueban directo con el monto), solo vehículo sigue
        // exigiéndola. MODIFY es sintaxis exclusiva de MySQL; en SQLite (tests
        // en memoria) la columna ya es nullable por defecto sin restricción.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE vales MODIFY comprobante VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE vales SET comprobante = '' WHERE comprobante IS NULL");

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE vales MODIFY comprobante VARCHAR(255) NOT NULL');
    }
};
