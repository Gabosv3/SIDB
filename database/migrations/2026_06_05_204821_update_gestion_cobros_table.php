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
        Schema::table('gestion_cobros', function (Blueprint $table) {
            $table->decimal('monto_pagado', 12, 2)->default(0)->after('monto_cuota')->comment('Monto pagado hasta el momento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestion_cobros', function (Blueprint $table) {
            $table->dropColumn('monto_pagado');
        });
    }
};
