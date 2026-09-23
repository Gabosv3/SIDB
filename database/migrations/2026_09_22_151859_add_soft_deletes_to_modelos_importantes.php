<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Soft delete para los modelos más importantes/con más historial: en vez de
// bloquear el borrado cuando ya tienen ventas/pagos/rutas/etc (o peor,
// borrarlos en cascada de un jalón sin aviso), ahora se pueden "borrar" de
// forma reversible -- el registro se oculta pero nada de su historial
// relacionado se pierde ni truena por restricción de llave foránea, porque
// ya no es un DELETE real sino un UPDATE de deleted_at.
return new class extends Migration
{
    private const TABLAS = [
        'clientes',
        'ventas',
        'productos',
        'vendedores',
        'cobradores',
        'rutas_cobro',
        'sucursales',
        'users',
        'compras',
        'proveedores',
        'supervisores',
        'vehiculos',
    ];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
