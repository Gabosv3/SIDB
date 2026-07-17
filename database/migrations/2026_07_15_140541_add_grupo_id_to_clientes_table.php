<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Sin FK: es una etiqueta compartida entre clientes vinculados (mismo
            // hogar/grupo familiar), no una referencia estricta a una fila "dueña".
            $table->unsignedBigInteger('grupo_id')->nullable()->after('activo');
            $table->index('grupo_id', 'idx_clientes_grupo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex('idx_clientes_grupo_id');
            $table->dropColumn('grupo_id');
        });
    }
};
