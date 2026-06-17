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
        // Agregar prima a ventas
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('prima', 12, 2)->default(0)->after('total');
        });

        // Cambiar enum tipo_pago en ventas para incluir mixta
        DB::statement("ALTER TABLE ventas MODIFY tipo_pago ENUM('contado','credito','mixta') NOT NULL DEFAULT 'contado'");

        // Agregar tipo_pago por línea en detalle_ventas
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->enum('tipo_pago', ['contado', 'credito'])->default('contado')->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropColumn('tipo_pago');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('prima');
        });

        DB::statement("ALTER TABLE ventas MODIFY tipo_pago ENUM('contado','credito') NOT NULL DEFAULT 'contado'");
    }
};
