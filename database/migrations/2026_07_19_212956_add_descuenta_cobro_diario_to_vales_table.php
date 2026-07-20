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
        Schema::table('vales', function (Blueprint $table) {
            // true: gasto chico que el empleado ya pagó de su bolsillo/de lo cobrado
            // ese día (imprevisto de calle, gasolina, consumo) — se resta del
            // efectivo que debe entregar en Resumen del Día. false: gasto grande
            // que el administrador registra directo (ej. reparación mayor), lo
            // paga la empresa aparte y no se descuenta del cobro diario del empleado.
            $table->boolean('descuenta_cobro_diario')->default(true)->after('categoria_vehiculo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->dropColumn('descuenta_cobro_diario');
        });
    }
};
