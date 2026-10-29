<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('whereFecha filtra igual que whereDate, incluyendo el borde del dia', function () {
    $ts = ['2026-10-13 23:59:59', '2026-10-14 00:00:00', '2026-10-14 12:30:00', '2026-10-14 23:59:59', '2026-10-15 00:00:00'];
    foreach ($ts as $i => $t) {
        DB::table('sucursales')->insert([
            'nombre' => "S{$i}", 'codigo' => "C{$i}", 'direccion' => 'x', 'telefono' => '+50312345678',
            'email' => "s{$i}@t.com", 'activo' => true, 'created_at' => $t, 'updated_at' => $t,
        ]);
    }

    foreach (['=', '<', '<=', '>', '>='] as $op) {
        $nuevo = DB::table('sucursales')->whereFecha('created_at', $op, '2026-10-14')->orderBy('id')->pluck('nombre')->all();
        $viejo = DB::table('sucursales')->whereDate('created_at', $op, '2026-10-14')->orderBy('id')->pluck('nombre')->all();
        expect($nuevo)->toBe($viejo, "operador {$op}");
    }

    // Forma corta (igual a '=') y con Carbon.
    expect(DB::table('sucursales')->whereFecha('created_at', '2026-10-14')->count())->toBe(3)
        ->and(DB::table('sucursales')->whereFecha('created_at', now()->setDate(2026, 10, 14))->count())->toBe(3);
});

test('whereFecha funciona tambien sobre modelos y relaciones', function () {
    expect(App\Models\PagoVenta::whereFecha('fecha_pago', today())->whereNull('anulado_en')->count())->toBe(0);
});

test('la migracion de indices deja los indices esperados', function () {
    foreach ([
        ['pago_ventas', 'pago_ventas_fecha_pago_index'], ['pago_ventas', 'pago_ventas_user_id_fecha_pago_index'],
        ['ventas', 'ventas_fecha_venta_index'], ['ventas', 'ventas_estado_index'],
        ['clientes', 'clientes_telefono_normal_index'], ['reintegros', 'reintegros_venta_id_estado_index'],
        ['gestion_cobros', 'gestion_cobros_venta_estado_vencimiento_index'],
    ] as [$tabla, $indice]) {
        expect(Schema::hasIndex($tabla, $indice))->toBeTrue("{$tabla}.{$indice}");
    }
});
