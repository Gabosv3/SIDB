<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_ruta_cobro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->cascadeOnDelete();
            $table->foreignId('ruta_cobro_id')
                ->constrained('rutas_cobro')
                ->cascadeOnDelete();
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->unique(['cliente_id', 'ruta_cobro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_ruta_cobro');
    }
};
