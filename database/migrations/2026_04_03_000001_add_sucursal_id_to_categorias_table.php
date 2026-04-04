<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Primero eliminamos el índice UNIQUE en 'nombre' porque ahora el nombre
        // puede repetirse entre sucursales distintas (ej: 'Bebidas' en Central y en Norte)
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->foreignId('sucursal_id')
                ->nullable()
                ->after('id')
                ->constrained('sucursales')
                ->nullOnDelete();

            // Nuevo índice único: nombre + sucursal (permite el mismo nombre en distintas sucursales)
            $table->unique(['sucursal_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropUnique(['sucursal_id', 'nombre']);
            $table->dropForeign(['sucursal_id']);
            $table->dropColumn('sucursal_id');
            $table->unique('nombre');
        });
    }
};
