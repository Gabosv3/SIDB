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

test('una compra con impuesto o descuento vacios se guarda con 0 y no revienta', function () {
    $prov = new Proveedor();
    $prov->forceFill(['nombre' => 'Prov', 'codigo' => 'PR' . random_int(1, 99999), 'email' => 'p2@test.com', 'telefono' => '70000001', 'activo' => true,
        'contacto_principal' => 'x', 'direccion' => 'x', 'ciudad' => 'x', 'departamento' => 'x', 'pais' => 'SV'])->save();

    // El formulario de Compras manda null cuando se deja vacio "Impuesto (%)" o "Descuento".
    $c = Compra::create(['numero_compra' => 'C-' . random_int(1, 99999), 'proveedor_id' => $prov->id, 'usuario_id' => $this->user->id,
        'fecha_compra' => now(), 'estado' => 'pendiente', 'forma_pago' => 'credito', 'subtotal' => 883.2, 'total' => 883.2,
        'saldo_pendiente' => 883.2, 'impuesto_porcentaje' => null, 'descuento_monto' => null, 'impuesto_monto' => null]);

    $c->refresh();
    expect((float) $c->impuesto_porcentaje)->toBe(0.0)
        ->and((float) $c->descuento_monto)->toBe(0.0)
        ->and((float) $c->impuesto_monto)->toBe(0.0)
        ->and((float) $c->total)->toBe(883.2);
});

test('el codigo automatico de vendedor no se repite aunque el ultimo este eliminado', function () {
    $nuevo = fn (string $email) => Vendedor::create([
        'nombre' => 'Ana', 'apellido' => 'Lopez', 'email' => $email, 'telefono' => '+50311100000',
        'activo' => true, 'sucursal_id' => $this->sucursal->id,
    ]);

    $a = $nuevo('a1@test.com');
    $b = $nuevo('a2@test.com');
    $b->delete(); // soft delete: el unico de la BD igual cuenta su codigo

    $c = $nuevo('a3@test.com');

    expect($c->codigo)->not->toBe($a->codigo)->and($c->codigo)->not->toBe($b->codigo);
});

test('replicar la asignacion del dia anterior copia productos, descuenta stock y respeta el tope de stock', function () {
    $a = ($this->producto)('R-1', 10);
    $b = ($this->producto)('R-2', 10);

    $ayer = App\Models\AsignacionDiaria::create([
        'vendedor_id' => $this->vendedor->id, 'sucursal_id' => $this->sucursal->id, 'fecha' => today()->subDay(), 'estado' => 'liquidada',
    ]);
    App\Models\DetalleAsignacion::create(['asignacion_id' => $ayer->id, 'producto_id' => $a->id, 'cantidad_asignada' => 4, 'precio_venta' => 2]);
    App\Models\DetalleAsignacion::create(['asignacion_id' => $ayer->id, 'producto_id' => $b->id, 'cantidad_asignada' => 8, 'precio_venta' => 2]);
    // Stock tras la asignacion de ayer: a=6, b=2 -> hoy solo alcanza para 2 de b.

    $r = App\Services\ReplicarAsignacionService::replicar($this->vendedor->id, today()->toDateString());

    expect($r['asignacion']->estado)->toBe('activa')
        ->and($r['asignacion']->detalles()->count())->toBe(2)
        ->and($r['asignacion']->detalles()->where('producto_id', $b->id)->value('cantidad_asignada'))->toBe(2)
        ->and($r['ajustes'])->toHaveCount(1)
        ->and($a->fresh()->stock)->toBe(2)
        ->and($b->fresh()->stock)->toBe(0);

    // No se puede replicar dos veces el mismo dia.
    expect(fn () => App\Services\ReplicarAsignacionService::replicar($this->vendedor->id, today()->toDateString()))
        ->toThrow(RuntimeException::class);
});

test('corregir fechas: invierte dia/mes de la venta, mueve sus cuotas y deja registro; sin --aplicar no escribe', function () {
    $cliente = ($this->cliente)();
    // Registrada el 15-jul pero con fecha 03-dic (en realidad 12-mar): dia y mes invertidos.
    $venta = ($this->venta)($cliente, ['fecha_venta' => '2026-12-03 00:00:00']);
    DB::table('ventas')->where('id', $venta->id)->update(['created_at' => '2026-07-15 10:00:00']);
    foreach ([1, 2] as $n) {
        App\Models\GestionCobro::create([
            'venta_id' => $venta->id, 'cliente_id' => $cliente->id, 'numero_cuota' => $n, 'total_cuotas' => 2,
            'monto_cuota' => 50, 'monto_pagado' => 0, 'estado' => 'pendiente',
            'fecha_vencimiento' => \Illuminate\Support\Carbon::parse('2026-12-03')->addDays(14 * $n)->toDateString(),
        ]);
    }
    // Una venta normal no se toca.
    $normal = ($this->venta)($cliente, ['fecha_venta' => '2026-07-01 09:00:00']);

    $this->artisan('ventas:corregir-fechas')->assertSuccessful();
    expect(DB::table('ventas')->where('id', $venta->id)->value('fecha_venta'))->toStartWith('2026-12-03');

    $this->artisan('ventas:corregir-fechas', ['--aplicar' => true])->assertSuccessful();

    expect(DB::table('ventas')->where('id', $venta->id)->value('fecha_venta'))->toStartWith('2026-03-12')
        ->and(DB::table('gestion_cobros')->where('venta_id', $venta->id)->orderBy('numero_cuota')->pluck('fecha_vencimiento')->map(fn ($f) => substr($f, 0, 10))->all())
        ->toBe(['2026-03-26', '2026-04-09'])
        ->and(DB::table('ventas')->where('id', $normal->id)->value('fecha_venta'))->toStartWith('2026-07-01')
        ->and(DB::table('activity_log')->where('log_name', 'fechas_corregidas')->count())->toBe(1);
});

test('corregir fechas: una venta sin propuesta automatica solo se corrige con --manual', function () {
    $cliente = ($this->cliente)();
    $venta = ($this->venta)($cliente, ['fecha_venta' => '2059-02-20 00:00:00']);
    DB::table('ventas')->where('id', $venta->id)->update(['created_at' => '2026-07-26 10:00:00']);

    $this->artisan('ventas:corregir-fechas', ['--aplicar' => true])->assertSuccessful();
    expect(DB::table('ventas')->where('id', $venta->id)->value('fecha_venta'))->toStartWith('2059-02-20');

    $this->artisan('ventas:corregir-fechas', ['--aplicar' => true, '--manual' => ["{$venta->id}:2026-07-25"]])->assertSuccessful();
    expect(DB::table('ventas')->where('id', $venta->id)->value('fecha_venta'))->toStartWith('2026-07-25');

    $this->artisan('ventas:corregir-fechas', ['--manual' => ['mal']])->assertFailed();
});


test('mover pago: cambia cobrador y fecha sin tocar el recibo ni el saldo, y valida fecha y cobrador', function () {
    $c = ($this->cliente)();
    $v = ($this->venta)($c, ['fecha_venta' => today()->subDays(10)]);
    $pago = \App\Models\PagoVenta::create([
        'venta_id' => $v->id, 'cliente_id' => $c->id, 'user_id' => $this->user->id, 'monto' => 20,
        'fecha_pago' => today(), 'metodo_pago' => 'efectivo', 'numero_recibo' => 'REC-9-000001',
    ]);
    $saldoAntes = $v->fresh()->saldo_pendiente;

    $otroUser = User::factory()->create();
    $otro = App\Models\Cobrador::create([
        'codigo' => 'C-2', 'nombre' => 'Otro', 'apellido' => 'Cobrador', 'email' => 'otro@test.com',
        'telefono' => '+50311122244', 'activo' => true, 'sucursal_id' => $this->sucursal->id, 'user_id' => $otroUser->id,
    ]);
    $sinUsuario = App\Models\Cobrador::create([
        'codigo' => 'C-3', 'nombre' => 'Sin', 'apellido' => 'Usuario', 'email' => 'sin@test.com',
        'telefono' => '+50311122255', 'activo' => true, 'sucursal_id' => $this->sucursal->id,
    ]);

    $dia = today()->subDays(3)->toDateString();
    $r = App\Services\MoverPagoVentaService::mover([$pago->id], $otro->id, $dia, $this->user->id, 'se registró con otro cobrador');

    $pago->refresh();
    expect($r['cantidad'])->toBe(1)
        ->and($pago->user_id)->toBe($otroUser->id)
        ->and($pago->fecha_pago->toDateString())->toBe($dia)
        ->and($pago->numero_recibo)->toBe('REC-9-000001')
        ->and($v->fresh()->saldo_pendiente)->toBe($saldoAntes)
        ->and(DB::table('activity_log')->where('log_name', 'pagos_movidos')->count())->toBe(1);

    // Fecha futura, anterior a la venta, o cobrador sin usuario: se rechaza y no cambia nada.
    expect(fn () => App\Services\MoverPagoVentaService::mover([$pago->id], $otro->id, today()->addDay()->toDateString(), null))->toThrow(RuntimeException::class)
        ->and(fn () => App\Services\MoverPagoVentaService::mover([$pago->id], $otro->id, today()->subDays(30)->toDateString(), null))->toThrow(RuntimeException::class)
        ->and(fn () => App\Services\MoverPagoVentaService::mover([$pago->id], $sinUsuario->id, $dia, null))->toThrow(RuntimeException::class);
    expect($pago->fresh()->fecha_pago->toDateString())->toBe($dia);
});

test('excluir de reportes: el cobrador excluido no sale en los filtros ni en los resumenes de encuestas y reintegros', function () {
    $mk = fn (string $codigo, bool $excluir) => App\Models\Cobrador::create([
        'codigo' => $codigo, 'nombre' => "Nom{$codigo}", 'apellido' => 'Ape', 'email' => "{$codigo}@test.com",
        'telefono' => '+50311122200', 'activo' => true, 'sucursal_id' => $this->sucursal->id,
        'user_id' => User::factory()->create()->id, 'excluir_reportes' => $excluir,
    ]);
    $real = $mk('R1', false);
    $prueba = $mk('R2', true);

    foreach ([App\Filament\Pages\ResumenEncuestasCliente::class, App\Filament\Pages\ResumenReintegros::class, App\Filament\Pages\ClientesInactivos::class] as $pagina) {
        $ids = (new $pagina)->getCobradores()->pluck('id')->all();
        expect($ids)->toContain($real->id)->not->toContain($prueba->id);
    }

    $cliente = ($this->cliente)();
    $sup = App\Models\Supervisor::create(['user_id' => User::factory()->create()->id, 'nombre' => 'Sup', 'apellido' => 'X', 'email' => 'sup@test.com', 'activo' => true, 'sucursal_id' => $this->sucursal->id]);
    foreach ([$real, $prueba] as $c) {
        DB::table('encuestas_cliente')->insert([
            'supervisor_id' => $sup->id, 'cliente_id' => $cliente->id, 'cobrador_id' => $c->id, 'fecha' => today()->toDateString(),
            'resultado' => 'coincide', 'diferencia' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    $todos = App\Services\ResumenEncuestasClienteService::resumen(today()->toDateString());
    expect($todos->pluck('cobrador_id')->all())->toBe([$real->id]);
    // Si se elige al excluido a proposito, si se ve.
    expect(App\Services\ResumenEncuestasClienteService::resumen(today()->toDateString(), [$prueba->id])->pluck('cobrador_id')->all())->toBe([$prueba->id]);
});

test('perfil de empleado: el interruptor Excluir de reportes se guarda en el cobrador y no cambia si el formulario no lo trae', function () {
    \Illuminate\Support\Facades\Gate::before(fn () => true);
    $admin = User::factory()->create();
    $this->actingAs($admin);

    $empleado = User::factory()->create();
    $cobrador = App\Models\Cobrador::create([
        'codigo' => 'EX1', 'nombre' => 'Per', 'apellido' => 'Fil', 'email' => 'perfil@test.com', 'telefono' => '+50311122211',
        'activo' => true, 'sucursal_id' => $this->sucursal->id, 'user_id' => $empleado->id, 'excluir_reportes' => false,
    ]);
    $url = route('empleados.actualizarLaboral', [$this->sucursal->id, $empleado->id]);
    $base = ['tipo_empleado' => ['cobrador'], 'estado_laboral' => 'activo'];

    $this->post($url, $base + ['excluir_reportes_presente' => '1', 'excluir_reportes' => '1'])->assertRedirect();
    expect($cobrador->fresh()->excluir_reportes)->toBeTrue();

    // Formulario sin el interruptor (p. ej. otra pantalla): no toca el valor guardado.
    $this->post($url, $base)->assertRedirect();
    expect($cobrador->fresh()->excluir_reportes)->toBeTrue();

    // Interruptor presente y apagado: se desmarca.
    $this->post($url, $base + ['excluir_reportes_presente' => '1'])->assertRedirect();
    expect($cobrador->fresh()->excluir_reportes)->toBeFalse();
});

test('perfil de empleado: el interruptor Excluir de reportes aparece solo si la persona es cobrador', function () {
    \Illuminate\Support\Facades\Gate::before(fn () => true);
    $this->actingAs(User::factory()->create());

    $conCobrador = User::factory()->create();
    App\Models\Cobrador::create([
        'codigo' => 'EX2', 'nombre' => 'Con', 'apellido' => 'Cob', 'email' => 'con@test.com', 'telefono' => '+50311122212',
        'activo' => true, 'sucursal_id' => $this->sucursal->id, 'user_id' => $conCobrador->id,
    ]);
    $sinCobrador = User::factory()->create();

    $this->get(route('empleados.show', [$this->sucursal->id, $conCobrador->id]))->assertOk()->assertSee('Excluir de reportes y estadísticas');
    $this->get(route('empleados.show', [$this->sucursal->id, $sinCobrador->id]))->assertOk()->assertDontSee('Excluir de reportes y estadísticas');
});

test('corregir fechas: los pagos con fecha imposible quedan en la fecha de su venta; los normales y anulados no se tocan', function () {
    $cliente = ($this->cliente)();
    $pago = fn ($venta, string $fecha, string $creado, array $extra = []) => tap(\App\Models\PagoVenta::create([
        'venta_id' => $venta->id, 'cliente_id' => $cliente->id, 'user_id' => $this->user->id, 'monto' => 10,
        'fecha_pago' => $fecha, 'metodo_pago' => 'efectivo',
    ] + $extra), fn ($p) => DB::table('pago_ventas')->where('id', $p->id)->update(['created_at' => $creado]));

    // Venta con dia/mes invertidos: 03-dic en realidad 12-mar. Su primer pago quedo con la misma fecha mala.
    $venta = ($this->venta)($cliente, ['fecha_venta' => '2026-12-03 00:00:00']);
    DB::table('ventas')->where('id', $venta->id)->update(['created_at' => '2026-03-12 10:00:00']);
    $primero = $pago($venta, '2026-12-03', '2026-03-12 10:05:00');
    $normal = $pago($venta, '2026-04-20', '2026-04-20 09:00:00');
    $anulado = $pago($venta, '2026-12-03', '2026-03-12 10:06:00', ['anulado_en' => now()]);

    // Venta correcta (sin correccion) pero con un pago en el futuro: se pone en la fecha de su venta.
    $otra = ($this->venta)($cliente, ['fecha_venta' => today()->subDays(20)->toDateTimeString()]);
    DB::table('ventas')->where('id', $otra->id)->update(['created_at' => today()->subDays(20)->toDateTimeString()]);
    $futuro = $pago($otra, today()->addDays(10)->toDateString(), today()->subDays(19)->toDateTimeString());

    // Vista previa: no escribe nada.
    $this->artisan('ventas:corregir-fechas')->assertSuccessful();
    expect(substr(DB::table('pago_ventas')->where('id', $primero->id)->value('fecha_pago'), 0, 10))->toBe('2026-12-03');

    // --sin-pagos: corrige la venta pero no los pagos.
    $this->artisan('ventas:corregir-fechas', ['--aplicar' => true, '--sin-pagos' => true])->assertSuccessful();
    expect(substr(DB::table('ventas')->where('id', $venta->id)->value('fecha_venta'), 0, 10))->toBe('2026-03-12')
        ->and(substr(DB::table('pago_ventas')->where('id', $primero->id)->value('fecha_pago'), 0, 10))->toBe('2026-12-03');

    // Con pagos (la venta ya esta corregida: la regla lee su fecha actual).
    $this->artisan('ventas:corregir-fechas', ['--aplicar' => true])->assertSuccessful();
    $fecha = fn ($p) => substr(DB::table('pago_ventas')->where('id', $p->id)->value('fecha_pago'), 0, 10);
    expect($fecha($primero))->toBe('2026-03-12')
        ->and($fecha($normal))->toBe('2026-04-20')
        ->and($fecha($anulado))->toBe('2026-12-03')
        ->and($fecha($futuro))->toBe(today()->subDays(20)->toDateString())
        ->and(DB::table('activity_log')->where('log_name', 'fechas_corregidas')->where('properties', 'like', '%pago_id%')->count())->toBe(2);

    // Idempotente: una segunda corrida no cambia nada mas.
    $this->artisan('ventas:corregir-fechas', ['--aplicar' => true])->assertSuccessful();
    expect(DB::table('activity_log')->where('log_name', 'fechas_corregidas')->where('properties', 'like', '%pago_id%')->count())->toBe(2);
});

test('corregir fechas: en una sola corrida corrige la venta, sus cuotas y su pago con la misma fecha mala (manual 2026-01-01)', function () {
    $cliente = ($this->cliente)();
    $venta = ($this->venta)($cliente, ['fecha_venta' => '2059-02-20 00:00:00']);
    DB::table('ventas')->where('id', $venta->id)->update(['created_at' => '2026-07-26 10:00:00']);
    $pago = \App\Models\PagoVenta::create([
        'venta_id' => $venta->id, 'cliente_id' => $cliente->id, 'user_id' => $this->user->id, 'monto' => 10,
        'fecha_pago' => '2059-02-20', 'metodo_pago' => 'efectivo',
    ]);
    DB::table('pago_ventas')->where('id', $pago->id)->update(['created_at' => '2026-07-26 10:05:00']);

    // Sin fecha manual no hay salida: ni la venta ni el pago se tocan.
    $this->artisan('ventas:corregir-fechas', ['--aplicar' => true])->assertSuccessful();
    expect(substr(DB::table('pago_ventas')->where('id', $pago->id)->value('fecha_pago'), 0, 10))->toBe('2059-02-20');

    $this->artisan('ventas:corregir-fechas', ['--aplicar' => true, '--manual' => ["{$venta->id}:2026-01-01"]])->assertSuccessful();

    expect(substr(DB::table('ventas')->where('id', $venta->id)->value('fecha_venta'), 0, 10))->toBe('2026-01-01')
        ->and(substr(DB::table('pago_ventas')->where('id', $pago->id)->value('fecha_pago'), 0, 10))->toBe('2026-01-01');
});
