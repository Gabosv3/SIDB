<?php

use App\Models\AsignacionDiaria;
use App\Models\Categoria;
use App\Models\DetalleAsignacion;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sucursal = Sucursal::create([
        'nombre'   => 'Sucursal prueba',
        'codigo'   => 'S0001',
        'direccion'=> 'Av. Prueba 123',
        'telefono' => '+50312345678',
        'email'    => 'sucursal@test.com',
        'activo'   => true,
    ]);

    $this->categoria = Categoria::create([
        'sucursal_id' => $this->sucursal->id,
        'nombre'      => 'Bebidas',
        'descripcion' => 'Categoría de bebidas',
        'activo'      => true,
    ]);

    $this->user = User::factory()->create([
        'name'     => 'Vendedor prueba',
        'email'    => 'vendedor@test.com',
        'password' => bcrypt('secret123'),
    ]);

    $this->vendedor = Vendedor::create([
        'codigo'      => 'VEND-001',
        'nombre'      => 'Juan',
        'apellido'    => 'Pérez',
        'email'       => 'vendedor@test.com',
        'telefono'    => '+50311122233',
        'activo'      => true,
        'sucursal_id' => $this->sucursal->id,
        'user_id'     => $this->user->id,
    ]);
});

test('el vendedor solo ve los productos asignados de hoy con stock asignado y disponible', function () {
    $productoAsignado = Producto::create([
        'sucursal_id'   => $this->sucursal->id,
        'categoria_id'  => $this->categoria->id,
        'nombre'        => 'Refresco Cola',
        'codigo'        => 'PROD-001',
        'descripcion'   => 'Refresco cola 600ml',
        'unidad_medida' => 'unidad',
        'precio_compra' => 0.80,
        'precio_venta'  => 1.20,
        'stock'         => 10,
        'activo'        => true,
    ]);

    $productoNoAsignado = Producto::create([
        'sucursal_id'   => $this->sucursal->id,
        'categoria_id'  => $this->categoria->id,
        'nombre'        => 'Agua Mineral',
        'codigo'        => 'PROD-002',
        'descripcion'   => 'Agua mineral 500ml',
        'unidad_medida' => 'unidad',
        'precio_compra' => 0.50,
        'precio_venta'  => 0.90,
        'stock'         => 15,
        'activo'        => true,
    ]);

    $asignacion = AsignacionDiaria::create([
        'vendedor_id' => $this->vendedor->id,
        'sucursal_id' => $this->sucursal->id,
        'fecha'       => today(),
        'estado'      => 'activa',
    ]);

    DetalleAsignacion::create([
        'asignacion_id'   => $asignacion->id,
        'producto_id'     => $productoAsignado->id,
        'cantidad_asignada'=> 5,
        'cantidad_vendida' => 2,
        'cantidad_devuelta'=> 0,
        'precio_venta'     => $productoAsignado->precio_venta,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/productos');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.codigo', 'PROD-001');
    $response->assertJsonPath('data.0.stock_asignado', 5);
    $response->assertJsonPath('data.0.stock_disponible', 3);
    $response->assertJsonPath('data.0.cantidad_vendida', 2);
    $response->assertJsonMissingExact(['codigo' => 'PROD-002']);
});
