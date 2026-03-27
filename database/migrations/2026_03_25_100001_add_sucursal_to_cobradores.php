<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobradores', function (Blueprint $table) {
            // Si la columna no existe, la agregamos
            if (!Schema::hasColumn('cobradores', 'sucursal_id')) {
                $table->foreignId('sucursal_id')
                    ->after('id')
                    ->nullable()
                    ->constrained('sucursales')
                    ->onDelete('cascade');
            }
        });

        // Asignar sucursal 1 a todos los cobradores existentes
        DB::table('cobradores')
            ->whereNull('sucursal_id')
            ->update(['sucursal_id' => 1]);

        // Hacer la columna NOT NULL
        Schema::table('cobradores', function (Blueprint $table) {
            $table->unsignedBigInteger('sucursal_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cobradores', function (Blueprint $table) {
            $table->dropForeignIdFor('sucursal_id');
        });
    }
};
