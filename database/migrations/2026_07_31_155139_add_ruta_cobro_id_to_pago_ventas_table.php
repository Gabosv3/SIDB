<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda en el pago la ruta del cliente EN EL MOMENTO del cobro. Sin esto,
     * el Resumen del Día agrupaba los pagos por la ruta ACTUAL del cliente
     * (consultada en vivo) — si el cliente salía de su ruta el mismo día (por
     * ejemplo, al mandarlo a reintegro justo después de cobrarle), el pago
     * dejaba de coincidir con cualquier ruta y desaparecía por completo del
     * reporte, aunque el total en efectivo que debe el cobrador seguía
     * correcto (ese total no depende de la ruta). Quedaba un hueco explotable:
     * cobrar y mandar a reintegro el mismo día ocultaba el cobro del desglose
     * por ruta que se usa para auditar.
     */
    public function up(): void
    {
        Schema::table('pago_ventas', function (Blueprint $table) {
            $table->foreignId('ruta_cobro_id')->nullable()->after('cliente_id')
                ->constrained('rutas_cobro')->nullOnDelete();
        });

        // Backfill de filas existentes: no hay forma de saber la ruta exacta
        // que tenía el cliente el día del pago, así que se usa su ruta actual
        // como mejor aproximación disponible (igual que hacía el reporte antes).
        // La sintaxis UPDATE...JOIN es exclusiva de MySQL; en SQLite (tests en
        // memoria) no hay datos que backfillear de todos modos.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE pago_ventas pv
                INNER JOIN clientes c ON c.id = pv.cliente_id
                SET pv.ruta_cobro_id = c.ruta_cobro_id
                WHERE pv.ruta_cobro_id IS NULL AND c.ruta_cobro_id IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('pago_ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ruta_cobro_id');
        });
    }
};
