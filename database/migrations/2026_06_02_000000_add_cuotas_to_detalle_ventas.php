<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->integer('cuotas')->nullable()->after('descuento_porcentaje');
            $table->decimal('precio_cuota', 12, 2)->nullable()->after('cuotas');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropColumn(['cuotas', 'precio_cuota']);
        });
    }
};
