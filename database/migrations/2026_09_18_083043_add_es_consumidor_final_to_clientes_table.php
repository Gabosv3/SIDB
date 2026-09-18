<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Marca al cliente genérico "Consumidor Final" que se usa para
            // ventas al contado cuando el cliente no quiere que lo
            // registren — uno por sucursal, creado automáticamente la
            // primera vez que se necesita (ver VentaController).
            $table->boolean('es_consumidor_final')->default(false)->after('activo');
            $table->index(['sucursal_id', 'es_consumidor_final']);
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['sucursal_id', 'es_consumidor_final']);
            $table->dropColumn('es_consumidor_final');
        });
    }
};
