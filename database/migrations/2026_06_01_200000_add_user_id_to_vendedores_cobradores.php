<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vendedores: vincular a usuario + flag de cobrador dual
        Schema::table('vendedores', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->after('sucursal_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('es_cobrador')
                ->default(false)
                ->after('user_id')
                ->comment('Si true, este vendedor también opera como cobrador');
        });

        // Cobradores: vincular a usuario
        Schema::table('cobradores', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->after('activo')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendedores', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'es_cobrador']);
        });

        Schema::table('cobradores', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
