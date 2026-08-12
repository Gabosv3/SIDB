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
        Schema::create('pagares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('user_id')->constrained('users')->comment('Vendedor que generó el pagaré');
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->string('nombre_deudor');
            $table->string('dui');
            $table->string('direccion')->nullable();
            $table->string('lugar_firma')->nullable();
            $table->decimal('monto_financiado', 10, 2);
            $table->date('fecha_vencimiento')->nullable();
            $table->string('pdf');
            $table->timestamps();

            $table->index('cliente_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagares');
    }
};
