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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('dui', 15)->nullable()->unique();
            $table->string('nit', 20)->nullable()->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 255)->nullable()->unique();
            $table->string('direccion', 500)->nullable();
            $table->decimal('limite_credito', 10, 2)->default(0);
            $table->decimal('saldo', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
