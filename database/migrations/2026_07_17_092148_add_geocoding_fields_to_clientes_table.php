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
            $table->string('colonia', 150)->nullable()->after('distrito');
            $table->string('codigo_postal', 20)->nullable()->after('municipio');
            $table->string('pais', 100)->nullable()->after('departamento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['colonia', 'codigo_postal', 'pais']);
        });
    }
};
