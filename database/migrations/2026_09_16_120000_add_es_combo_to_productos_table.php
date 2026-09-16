<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Un combo es un producto normal (mismo código, precio, cuotas,
            // se asigna y se vende exactamente igual) pero SIN stock propio:
            // su stock se calcula solo a partir de sus componentes (ver
            // combo_componentes) y se recalcula cada vez que cambia el stock
            // de alguno de ellos.
            $table->boolean('es_combo')->default(false)->after('origen');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('es_combo');
        });
    }
};
