<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('producto_proveedor', function (Blueprint $table) {
                $table->decimal('precio_unitario', 10, 2)->nullable()->change();
            });

            return;
        }

        // El checkbox de "Proveedores" en el producto (Filament CheckboxList
        // con ->relationship()) hace sync() sin pasar precio_unitario -- con
        // la columna obligatoria, cada intento de marcar un proveedor tronaba
        // con "Field 'precio_unitario' doesn't have a default value".
        DB::statement('ALTER TABLE `producto_proveedor` MODIFY `precio_unitario` DECIMAL(10,2) NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('producto_proveedor', function (Blueprint $table) {
                $table->decimal('precio_unitario', 10, 2)->nullable(false)->change();
            });

            return;
        }

        DB::statement('ALTER TABLE `producto_proveedor` MODIFY `precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0');
    }
};
