<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Monto fijo que el vendedor debe entregar a la empresa por cada
            // unidad vendida, sin importar a qué precio la haya vendido al
            // cliente -- la diferencia entre precio_venta y este monto es la
            // ganancia del vendedor.
            $table->decimal('precio_vendedor', 10, 2)->nullable()->after('precio_venta');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('precio_vendedor');
        });
    }
};
