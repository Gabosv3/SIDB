<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobradores', function (Blueprint $table) {
            $table->renameColumn('excluir_resumen_diario', 'excluir_reportes');
        });
    }

    public function down(): void
    {
        Schema::table('cobradores', function (Blueprint $table) {
            $table->renameColumn('excluir_reportes', 'excluir_resumen_diario');
        });
    }
};
