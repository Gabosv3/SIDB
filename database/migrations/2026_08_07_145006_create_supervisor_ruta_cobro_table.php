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
        Schema::create('supervisor_ruta_cobro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained('supervisores')->cascadeOnDelete();
            $table->foreignId('ruta_cobro_id')->constrained('rutas_cobro')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['supervisor_id', 'ruta_cobro_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervisor_ruta_cobro');
    }
};
