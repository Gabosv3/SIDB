<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comision_tramos', function (Blueprint $table) {
            $table->id();
            // Tramos de comisión por volumen semanal vendido: a mayor venta,
            // menor porcentaje. hasta = null significa "sin límite superior"
            // (el último tramo). El % que aplica es el del tramo donde CAE el
            // total vendido de la semana -- no es acumulado como el ISR, todo
            // el monto paga el % de su propio tramo.
            $table->decimal('desde', 10, 2);
            $table->decimal('hasta', 10, 2)->nullable();
            $table->decimal('porcentaje', 5, 2);
            $table->timestamps();
        });

        DB::table('comision_tramos')->insert([
            ['desde' => 0,   'hasta' => 299.99, 'porcentaje' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['desde' => 300, 'hasta' => 599.99, 'porcentaje' => 8,  'created_at' => now(), 'updated_at' => now()],
            ['desde' => 600, 'hasta' => 899.99, 'porcentaje' => 6,  'created_at' => now(), 'updated_at' => now()],
            ['desde' => 900, 'hasta' => null,   'porcentaje' => 4,  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_tramos');
    }
};
