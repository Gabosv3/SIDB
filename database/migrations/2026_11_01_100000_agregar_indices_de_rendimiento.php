<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para los filtros que más se repiten (por fecha, estado y búsqueda de
 * teléfono). Solo agrega índices: no toca datos ni columnas, así que es seguro
 * correrla con la app móvil en uso. Cada índice se crea solo si todavía no existe.
 */
return new class extends Migration
{
    /** tabla => [nombre del índice => columnas] */
    private const INDICES = [
        'pago_ventas' => [
            'pago_ventas_fecha_pago_index' => ['fecha_pago'],
            'pago_ventas_user_id_fecha_pago_index' => ['user_id', 'fecha_pago'],
        ],
        'ventas' => [
            'ventas_fecha_venta_index' => ['fecha_venta'],
            'ventas_vendedor_id_fecha_venta_index' => ['vendedor_id', 'fecha_venta'],
            'ventas_estado_index' => ['estado'],
        ],
        'clientes' => [
            'clientes_telefono_normal_index' => ['telefono_normal'],
        ],
        'reintegros' => [
            'reintegros_venta_id_estado_index' => ['venta_id', 'estado'],
        ],
        'gestion_cobros' => [
            'gestion_cobros_venta_estado_vencimiento_index' => ['venta_id', 'estado', 'fecha_vencimiento'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDICES as $tabla => $indices) {
            foreach ($indices as $nombre => $columnas) {
                if (Schema::hasIndex($tabla, $nombre)) {
                    continue;
                }

                Schema::table($tabla, fn ($t) => $t->index($columnas, $nombre));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDICES as $tabla => $indices) {
            foreach (array_keys($indices) as $nombre) {
                if (Schema::hasIndex($tabla, $nombre)) {
                    Schema::table($tabla, fn ($t) => $t->dropIndex($nombre));
                }
            }
        }
    }
};
