<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutas_cobro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cobrador_id')
                ->constrained('cobradores')
                ->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->string('descripcion', 500)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['cobrador_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutas_cobro');
    }
};
