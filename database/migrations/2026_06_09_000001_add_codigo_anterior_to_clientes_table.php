<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('codigo_anterior', 100)->nullable()->after('id')
                  ->comment('Código en el sistema anterior (para importaciones)');
            $table->index('codigo_anterior');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['codigo_anterior']);
            $table->dropColumn('codigo_anterior');
        });
    }
};
