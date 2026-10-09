<?php

use App\Http\Controllers\AsignacionDiariaController;
use App\Models\AsignacionDiaria;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\DetalleAsignacion;
use App\Models\DetalleCompra;
use App\Models\MovimientoStock;
use App\Models\PagoCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Services\AnularVentaService;
use App\Services\CuadreCajaService;
use App\Services\ResumenMensualService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Algunas reglas consultan hasRole('super_admin'); Spatie lanza si el rol no existe.
    \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
    $this->sucursal = Sucursal::create([
        'nombre' => 'Sucursal prueba', 'codigo' => 'S0001', 'direccion' => 'Av. Prueba 123',
        'telefono' => '+50312345678', 'email' => 'sucursal@test.com', 'activo' => true,
    ]);
    $this->categoria = Categoria::create(['sucursal_id' => $this->sucursal->id, 'nombre' => 'General', 'descripcion' => 'x', 'activo' => true]);
    $this->user = User::factory()->create();
    $this->vendedor = Vendedor::create([
        'codigo' => 'V-1', 'nombre' => 'Juan', 'apellido' => 'Perez', 'email' => 'v@test.com',
        'telefono' => '+50311122233', 'activo' => true, 'sucursal_id' => $this->sucursal->id, 'user_id' => $this->user->id,
    ]);
    $this->producto = fn (string $codigo = 'P-1', int $stock = 0) => Producto::create([
        'sucursal_id' => $this->sucursal->id, 'categoria_id' => $this->categoria->id, 'nombre' => 'Producto ' . $codigo,
        'codigo' => $codigo, 'unidad_medida' => 'unidad', 'precio_compra' => 1, 'precio_venta' => 2, 'stock' => $stock, 'activo' => true,
    ]);
    $this->cliente = fn (array $extra = []) => Cliente::create(array_merge([
        'sucursal_id' => $this->sucursal->id, 'nombre' => 'Cli', 'apellido' => 'Ente', 'activo' => true, 'saldo' => 0,
    ], $extra));
    $this->venta = fn (Cliente $c, array $extra = []) => Venta::create(array_merge([
        'cliente_id' => $c->id, 'vendedor_id' => $this->vendedor->id, 'user_id' => $this->user->id, 'sucursal_id' => $this->sucursal->id,
        'tipo_pago' => 'credito', 'subtotal' => 100, 'total' => 100, 'saldo_pendiente' => 100, 'monto_pagado' => 0,
        'estado' => 'pendiente', 'fecha_venta' => now(),
    ], $extra));
    $this->compra = function (Producto $p, int $cantidad = 5) {
        $prov = new Proveedor();
        $prov->forceFill(['nombre' => 'Prov', 'codigo' => 'PR' . random_int(1, 99999), 'email' => 'p@test.com', 'telefono' => '70000000', 'activo' => true,
            'contacto_principal' => 'x', 'direccion' => 'x', 'ciudad' => 'x', 'departamento' => 'x', 'pais' => 'SV'])->save();
        $c = Compra::create(['numero_compra' => 'C-' . random_int(1, 99999), 'proveedor_id' => $prov->id, 'usuario_id' => $this->user->id,
            'fecha_compra' => now(), 'estado' => 'pendiente', 'forma_pago' => 'credito', 'subtotal' => 0, 'total' => 0, 'saldo_pendiente' => 0]);
        DetalleCompra::create(['compra_id' => $c->id, 'producto_id' => $p->id, 'cantidad' => $cantidad, 'precio_unitario' => 10, 'descuento_unitario' => 0]);

        return $c->refresh();
    };
});

// ── Códigos de cliente ───────────────────────────────────────────────────────

test('codigo interno arranca en 10000 y es correlativo', function () {
    $a = ($this->cliente)();
    $b = ($this->cliente)();

    expect($a->codigo)->toBe(10000)->and($b->codigo)->toBe(10001);
});

test('el codigo no se repite aunque el cliente con el maximo este en la papelera', function () {
    $a = ($this->cliente)();
    $b = ($this->cliente)();
    $b->delete();

    expect(($this->cliente)()->codigo)->toBe(10002);
});

test('codigo_anterior automatico es compacto desde 10001 y respeta los codigos heredados', function () {
    $legado = ($this->cliente)(['codigo_anterior' => '7338']);
    $a = ($this->cliente)();
    $b = ($this->cliente)();

    expect($legado->codigo_anterior)->toBe('7338')
        ->and($a->codigo_anterior)->toBe('10001')
        ->and($b->codigo_anterior)->toBe('10002');
});

// ── Saldo del cliente ────────────────────────────────────────────────────────

test('crear una venta a credito sube el saldo del cliente', function () {
    $c = ($this->cliente)();
    ($this->venta)($c);

    expect((float) $c->fresh()->saldo)->toBe(100.0);
});

test('cancelar una venta quita su deuda del saldo del cliente', function () {
    $c = ($this->cliente)();
    ($this->venta)($c);
    $v2 = ($this->venta)($c, ['total' => 50, 'saldo_pendiente' => 50, 'subtotal' => 50]);

    expect((float) $c->fresh()->saldo)->toBe(150.0);

    $r = AnularVentaService::anular($v2->fresh(), 'prueba');

    expect($r)->toHaveKey('ok')->and((float) $c->fresh()->saldo)->toBe(100.0);
});

test('las ventas canceladas o devueltas no cuentan en el saldo', function () {
    $c = ($this->cliente)();
    ($this->venta)($c, ['estado' => 'devuelta']);

    expect((float) $c->fresh()->saldo)->toBe(0.0);
});

// ── Compras: stock y pagos ───────────────────────────────────────────────────

test('marcar una compra como recibida sube el stock una sola vez', function () {
    $p = ($this->producto)('P-C1', 0);
    $c = ($this->compra)($p, 5);

    $c->update(['estado' => 'recibida']);
    $c->update(['estado' => 'pendiente']);
    $c->update(['estado' => 'recibida']);
    $c->update(['estado' => 'completada']);

    expect($p->fresh()->stock)->toBe(5);
});

test('cancelar o devolver una compra recibida descuenta el stock una sola vez', function () {
    $p = ($this->producto)('P-C2', 0);
    $c = ($this->compra)($p, 5);
    $c->update(['estado' => 'recibida']);

    $c->update(['estado' => 'cancelada']);
    expect($p->fresh()->stock)->toBe(0);

    $c->update(['estado' => 'devuelta']);
    expect($p->fresh()->stock)->toBe(0);

    $c->update(['estado' => 'recibida']);
    expect($p->fresh()->stock)->toBe(5);
});

test('cancelar una compra que nunca ingreso no mueve el stock', function () {
    $p = ($this->producto)('P-C3', 3);
    $c = ($this->compra)($p, 4);

    $c->update(['estado' => 'cancelada']);

    expect($p->fresh()->stock)->toBe(3);
});

test('el saldo de la compra respeta los pagos aunque se editen las lineas', function () {
    $p = ($this->producto)('P-C4', 0);
    $c = ($this->compra)($p, 5); // total 50

    PagoCompra::create(['compra_id' => $c->id, 'fecha_pago' => now(), 'monto' => 20, 'forma_pago' => 'efectivo', 'usuario_id' => $this->user->id]);
    expect((float) $c->fresh()->saldo_pendiente)->toBe(30.0);

    $c->detalles()->first()->update(['cantidad' => 6]); // total 60
    expect((float) $c->fresh()->total)->toBe(60.0)->and((float) $c->fresh()->saldo_pendiente)->toBe(40.0);
});

// ── Asignaciones diarias ─────────────────────────────────────────────────────

test('editar una asignacion diaria no vuelve a descontar el stock', function () {
    $this->actingAs($this->user);
    $p1 = ($this->producto)('P-A1', 100);
    $p2 = ($this->producto)('P-A2', 100);

    $a = AsignacionDiaria::create(['vendedor_id' => $this->vendedor->id, 'sucursal_id' => $this->sucursal->id, 'fecha' => today()->toDateString(), 'estado' => 'activa']);
    DetalleAsignacion::create(['asignacion_id' => $a->id, 'producto_id' => $p1->id, 'cantidad_asignada' => 10, 'precio_venta' => 5]);
    expect($p1->fresh()->stock)->toBe(90);

    $editar = fn (array $det) => (new AsignacionDiariaController())->actualizar(
        Request::create('/x', 'POST', ['vendedor_id' => $this->vendedor->id, 'sucursal_id' => $this->sucursal->id, 'fecha' => $a->fecha->toDateString(), 'detalles' => $det]),
        $this->sucursal->id,
        $a->fresh()
    );

    $editar([['producto_id' => $p1->id, 'cantidad_asignada' => 10, 'precio_venta' => 5]]);
    expect($p1->fresh()->stock)->toBe(90);

    $editar([['producto_id' => $p1->id, 'cantidad_asignada' => 7, 'precio_venta' => 5]]);
    expect($p1->fresh()->stock)->toBe(93);

    $editar([['producto_id' => $p2->id, 'cantidad_asignada' => 3, 'precio_venta' => 5]]);
    expect($p1->fresh()->stock)->toBe(100)->and($p2->fresh()->stock)->toBe(97);
});

// ── Resumen mensual y cuadre de caja ─────────────────────────────────────────

test('el resumen mensual suma las ventas del mes y excluye canceladas', function () {
    $c = ($this->cliente)();
    ($this->venta)($c, ['total' => 100, 'saldo_pendiente' => 100]);
    ($this->venta)($c, ['total' => 40, 'saldo_pendiente' => 40, 'estado' => 'cancelada']);

    $r = ResumenMensualService::calcular(Carbon::now()->startOfMonth());

    expect($r['ventas']['cantidad'])->toBe(1)
        ->and($r['ventas']['total'])->toBe(100.0)
        ->and($r['ventas']['anuladas_cantidad'])->toBe(1)
        ->and($r['flujo']['entradas'])->toBe(0.0);
});

test('el cuadre de caja calcula lo que debe entregar una persona', function () {
    $c = ($this->cliente)();
    $v = ($this->venta)($c);
    \App\Models\PagoVenta::create(['venta_id' => $v->id, 'cliente_id' => $c->id, 'user_id' => $this->user->id, 'monto' => 20, 'fecha_pago' => today(), 'metodo_pago' => 'efectivo']);
    $vale = new \App\Models\Vale();
    $vale->forceFill(['user_id' => $this->user->id, 'sucursal_id' => $this->sucursal->id, 'tipo' => 'consumo', 'monto' => 3, 'comprobante' => 'x.jpg',
        'fecha_gasto' => today()->toDateString(), 'estado' => 'pendiente', 'descuenta_cobro_diario' => true])->save();

    $fila = CuadreCajaService::filas(today())->firstWhere('user_id', $this->user->id);

    expect($fila['cobros'])->toBe(20.0)->and($fila['gastos'])->toBe(3.0)->and($fila['esperado'])->toBe(17.0);
});
