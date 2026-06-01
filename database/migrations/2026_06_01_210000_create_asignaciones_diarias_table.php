<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Asignaciones diarias ─────────────────────────────────────────────
        Schema::create('asignaciones_diarias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendedor_id')
                ->constrained('vendedores')
                ->cascadeOnDelete();

            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->cascadeOnDelete();

            $table->date('fecha')->comment('Fecha de la jornada');

            $table->enum('estado', ['activa', 'liquidada'])->default('activa');

            // Totales calculados al liquidar
            $table->decimal('total_vendido', 12, 2)->nullable();
            $table->decimal('total_devuelto_valor', 12, 2)->nullable();
            $table->timestamp('liquidada_at')->nullable();

            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Un vendedor solo puede tener una asignación activa por día
            $table->unique(['vendedor_id', 'fecha'], 'unique_vendedor_fecha');
        });

        // ── Detalle de productos asignados ───────────────────────────────────
        Schema::create('detalle_asignaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asignacion_id')
                ->constrained('asignaciones_diarias')
                ->cascadeOnDelete();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->cascadeOnDelete();

            $table->integer('cantidad_asignada')->default(0)
                ->comment('Unidades que el vendedor lleva al salir');

            // Se calculan al liquidar
            $table->integer('cantidad_vendida')->default(0)
                ->comment('Unidades vendidas durante la jornada');

            $table->integer('cantidad_devuelta')->default(0)
                ->comment('Unidades que regresaron sin vender');

            $table->decimal('precio_venta', 10, 2)->nullable()
                ->comment('Precio vigente al momento de la asignación');

            $table->timestamps();

            $table->unique(['asignacion_id', 'producto_id'], 'unique_asignacion_producto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_asignaciones');
        Schema::dropIfExists('asignaciones_diarias');
    }
};
