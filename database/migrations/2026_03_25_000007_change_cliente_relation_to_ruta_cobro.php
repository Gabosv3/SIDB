<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quitar la relación directa con cobrador
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cobrador_id');
        });

        // Agregar relación con ruta de cobro
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('ruta_cobro_id')
                ->nullable()
                ->after('activo')
                ->constrained('rutas_cobro')
                ->cascadeOnDelete();
        });

        // Eliminar tabla pivote si existe
        if (Schema::hasTable('cliente_ruta_cobro')) {
            Schema::dropIfExists('cliente_ruta_cobro');
        }
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ruta_cobro_id');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('cobrador_id')
                ->nullable()
                ->after('activo')
                ->constrained('cobradores')
                ->cascadeOnDelete();
        });
    }
};
