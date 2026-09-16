<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combo_componentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_combo_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('producto_componente_id')->constrained('productos')->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamps();

            $table->unique(['producto_combo_id', 'producto_componente_id'], 'combo_componente_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_componentes');
    }
};
