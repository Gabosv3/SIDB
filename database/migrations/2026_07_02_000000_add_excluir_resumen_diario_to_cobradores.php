<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobradores', function (Blueprint $table) {
            $table->boolean('excluir_resumen_diario')
                ->after('activo')
                ->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('cobradores', function (Blueprint $table) {
            $table->dropColumn('excluir_resumen_diario');
        });
    }
};
