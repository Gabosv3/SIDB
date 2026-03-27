<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (!Schema::hasColumn('clientes', 'sucursal_id')) {
                $table->foreignId('sucursal_id')
                    ->after('id')
                    ->nullable()
                    ->constrained('sucursales')
                    ->onDelete('cascade');
            }
        });

        DB::table('clientes')
            ->whereNull('sucursal_id')
            ->update(['sucursal_id' => 1]);

        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('sucursal_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeignIdFor('sucursal_id');
        });
    }
};
