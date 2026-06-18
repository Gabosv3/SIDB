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
        Schema::create('visitas_cobro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('gestion_cobro_id')->nullable()->constrained('gestion_cobros');
            $table->dateTime('fecha_visita');
            $table->enum('resultado', ['sin_pago', 'promesa_pago', 'no_encontrado', 'rechazo']);
            $table->date('promesa_fecha')->nullable();
            $table->string('foto_hogar')->nullable();
            $table->text('observaciones')->nullable();
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitas_cobro');
    }
};
