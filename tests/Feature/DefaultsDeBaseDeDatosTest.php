<?php

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\User;
use App\Support\DefaultsDeBaseDeDatos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;

uses(RefreshDatabase::class);

beforeEach(function () {
    DefaultsDeBaseDeDatos::olvidarCache();
    $this->user = User::factory()->create();
    $prov = new Proveedor();
    $prov->forceFill(['nombre' => 'Prov', 'codigo' => 'PR' . random_int(1, 99999), 'email' => 'd@test.com', 'telefono' => '70000002',
        'activo' => true, 'contacto_principal' => 'x', 'direccion' => 'x', 'ciudad' => 'x', 'departamento' => 'x', 'pais' => 'SV'])->save();
    $this->proveedor = $prov;
    $this->datosCompra = fn (array $extra = []) => array_merge([
        'numero_compra' => 'C-' . random_int(1, 999999), 'proveedor_id' => $this->proveedor->id, 'usuario_id' => $this->user->id,
        'fecha_compra' => now(),
    ], $extra);
});

test('un null en columnas con default numerico se guarda con el default', function () {
    $c = Compra::create(($this->datosCompra)(['impuesto_porcentaje' => null, 'descuento_monto' => null, 'dias_credito' => null]))->refresh();

    expect((float) $c->impuesto_porcentaje)->toBe(0.0)
        ->and((float) $c->descuento_monto)->toBe(0.0)
        ->and((int) $c->dias_credito)->toBe(0);
});

test('un null en columnas con default de texto o enum usa el default sin comillas', function () {
    $c = Compra::create(($this->datosCompra)(['forma_pago' => null, 'condicion_pago' => null, 'estado' => null]))->refresh();

    expect($c->forma_pago)->toBe('credito')
        ->and($c->condicion_pago)->toBe('contado')
        ->and($c->estado)->toBe('pendiente');
});

test('al editar, un null tambien se convierte en el default', function () {
    $c = Compra::create(($this->datosCompra)(['impuesto_porcentaje' => 16]));
    expect((float) $c->fresh()->impuesto_porcentaje)->toBe(16.0);

    $c->update(['impuesto_porcentaje' => null]);

    expect((float) $c->fresh()->impuesto_porcentaje)->toBe(0.0);
});

test('un valor real nunca se pisa con el default', function () {
    $c = Compra::create(($this->datosCompra)(['impuesto_porcentaje' => 13, 'forma_pago' => 'efectivo']))->refresh();

    expect((float) $c->impuesto_porcentaje)->toBe(13.0)->and($c->forma_pago)->toBe('efectivo');
});

test('un campo realmente obligatorio sin default sigue fallando en vez de inventar un valor', function () {
    expect(fn () => Compra::create(($this->datosCompra)(['numero_compra' => null])))->toThrow(QueryException::class);
});

test('la defensa solo actua sobre modelos propios de la app', function () {
    $modelo = new class extends Illuminate\Database\Eloquent\Model {
        protected $table = 'compras';
    };
    $modelo->setRawAttributes(['impuesto_porcentaje' => null]);

    DefaultsDeBaseDeDatos::aplicar($modelo);

    expect($modelo->getAttributes()['impuesto_porcentaje'])->toBeNull();
});

test('las peticiones a la API movil (api/*) quedan fuera de la defensa', function () {
    app()->instance('request', Illuminate\Http\Request::create('/api/ventas', 'POST'));

    expect(fn () => Compra::create(($this->datosCompra)(['impuesto_porcentaje' => null])))->toThrow(QueryException::class);
});

test('las peticiones del panel si usan la defensa', function () {
    app()->instance('request', Illuminate\Http\Request::create('/administrativo/1/compras/create', 'POST'));

    $c = Compra::create(($this->datosCompra)(['impuesto_porcentaje' => null]))->refresh();

    expect((float) $c->impuesto_porcentaje)->toBe(0.0);
});
