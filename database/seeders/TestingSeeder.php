<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cobrador;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\RutaCobro;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestingSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Roles base ─────────────────────────────────────────────────────
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin',      'guard_name' => 'web']);
        $roleAdmin      = Role::firstOrCreate(['name' => 'administrador',    'guard_name' => 'web']);
        $roleVendedor   = Role::firstOrCreate(['name' => 'vendedor',         'guard_name' => 'web']);

        // ── 2. Sucursales ─────────────────────────────────────────────────────
        $sucursalCentral = Sucursal::firstOrCreate(
            ['codigo' => 'CENTRAL'],
            [
                'nombre'    => 'Distribuidora Central',
                'direccion' => '1ª Calle Poniente #24, San Salvador',
                'telefono'  => '2222-1111',
                'email'     => 'central@sidb.com',
                'activo'    => true,
            ]
        );

        $sucursalNorte = Sucursal::firstOrCreate(
            ['codigo' => 'NORTE'],
            [
                'nombre'    => 'Sucursal Norte',
                'direccion' => 'Av. Norte #15, Santa Ana',
                'telefono'  => '2444-5555',
                'email'     => 'norte@sidb.com',
                'activo'    => true,
            ]
        );

        // ── 3. Usuarios ───────────────────────────────────────────────────────

        // Super admin: acceso a ambas sucursales
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@sidb.com'],
            [
                'name'     => 'Administrador General',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->syncRoles([$roleSuperAdmin]);
        $superAdmin->sucursales()->syncWithoutDetaching([$sucursalCentral->id, $sucursalNorte->id]);

        // Manager sucursal Central
        $managerCentral = User::firstOrCreate(
            ['email' => 'central@sidb.com'],
            [
                'name'     => 'Manager Central',
                'password' => Hash::make('password'),
            ]
        );
        $managerCentral->syncRoles([$roleAdmin]);
        $managerCentral->sucursales()->syncWithoutDetaching([$sucursalCentral->id]);

        // Manager sucursal Norte
        $managerNorte = User::firstOrCreate(
            ['email' => 'norte@sidb.com'],
            [
                'name'     => 'Manager Norte',
                'password' => Hash::make('password'),
            ]
        );
        $managerNorte->syncRoles([$roleAdmin]);
        $managerNorte->sucursales()->syncWithoutDetaching([$sucursalNorte->id]);

        // Vendedor solo en sucursal Central
        $vendedorUser = User::firstOrCreate(
            ['email' => 'vendedor@sidb.com'],
            [
                'name'     => 'Juan Vendedor',
                'password' => Hash::make('password'),
            ]
        );
        $vendedorUser->syncRoles([$roleVendedor]);
        $vendedorUser->sucursales()->syncWithoutDetaching([$sucursalCentral->id]);

        // ── 4. Categorías POR SUCURSAL ────────────────────────────────────────
        // Limpiamos categorías huérfanas (sin sucursal) si quedaron de seeders anteriores
        Categoria::whereNull('sucursal_id')->delete();

        // Categorías para Central
        $catBebidasCentral = Categoria::firstOrCreate(
            ['nombre' => 'Bebidas', 'sucursal_id' => $sucursalCentral->id],
            ['descripcion' => 'Refrescos, agua y jugos', 'activo' => true]
        );
        $catAlimentosCentral = Categoria::firstOrCreate(
            ['nombre' => 'Alimentos', 'sucursal_id' => $sucursalCentral->id],
            ['descripcion' => 'Productos alimenticios en general', 'activo' => true]
        );
        $catLimpiezaCentral = Categoria::firstOrCreate(
            ['nombre' => 'Limpieza', 'sucursal_id' => $sucursalCentral->id],
            ['descripcion' => 'Artículos de limpieza del hogar', 'activo' => true]
        );
        $catPersonalCentral = Categoria::firstOrCreate(
            ['nombre' => 'Cuidado Personal', 'sucursal_id' => $sucursalCentral->id],
            ['descripcion' => 'Higiene y cuidado personal', 'activo' => true]
        );

        // Categorías para Norte (pueden ser distintas o con mismo nombre)
        $catBebidasNorte = Categoria::firstOrCreate(
            ['nombre' => 'Bebidas', 'sucursal_id' => $sucursalNorte->id],
            ['descripcion' => 'Refrescos y bebidas', 'activo' => true]
        );
        $catAlimentosNorte = Categoria::firstOrCreate(
            ['nombre' => 'Alimentos', 'sucursal_id' => $sucursalNorte->id],
            ['descripcion' => 'Granos, especias y abarrotes', 'activo' => true]
        );

        // ── 5. Proveedores (globales) ─────────────────────────────────────────
        $proveedor1 = Proveedor::firstOrCreate(
            ['codigo' => 'PROV-001'],
            [
                'nombre'             => 'Distribuidora El Sol S.A.',
                'contacto_principal' => 'Carlos Ramírez',
                'email'              => 'ventas@elsol.com',
                'telefono'           => '2233-4455',
                'direccion'          => 'Zona Industrial, Soyapango',
                'ciudad'             => 'Soyapango',
                'departamento'       => 'San Salvador',
                'pais'               => 'El Salvador',
                'condiciones_pago'   => 'credito',
                'dias_credito'       => 30,
                'activo'             => true,
            ]
        );

        $proveedor2 = Proveedor::firstOrCreate(
            ['codigo' => 'PROV-002'],
            [
                'nombre'             => 'Industrias Nacionales LTDA',
                'contacto_principal' => 'María López',
                'email'              => 'pedidos@nacionales.com',
                'telefono'           => '2266-7788',
                'direccion'          => 'Blvd. Constitución #200',
                'ciudad'             => 'San Salvador',
                'departamento'       => 'San Salvador',
                'pais'               => 'El Salvador',
                'condiciones_pago'   => 'contado',
                'dias_credito'       => 0,
                'activo'             => true,
            ]
        );

        // ── 6. Productos por sucursal ─────────────────────────────────────────
        $productosCentral = [
            ['codigo' => 'PROD-001', 'nombre' => 'Refresco Cola 600ml',     'sucursal_id' => $sucursalCentral->id, 'categoria_id' => $catBebidasCentral->id,   'precio_compra' => 0.55, 'precio_venta' => 1.00, 'stock' => 200, 'stock_minimo' => 30, 'unidad_medida' => 'unidad'],
            ['codigo' => 'PROD-002', 'nombre' => 'Agua Purificada 500ml',   'sucursal_id' => $sucursalCentral->id, 'categoria_id' => $catBebidasCentral->id,   'precio_compra' => 0.25, 'precio_venta' => 0.50, 'stock' => 500, 'stock_minimo' => 50, 'unidad_medida' => 'unidad'],
            ['codigo' => 'PROD-003', 'nombre' => 'Jugo Natural Naranja',    'sucursal_id' => $sucursalCentral->id, 'categoria_id' => $catBebidasCentral->id,   'precio_compra' => 0.75, 'precio_venta' => 1.25, 'stock' => 150, 'stock_minimo' => 20, 'unidad_medida' => 'unidad'],
            ['codigo' => 'PROD-004', 'nombre' => 'Arroz Blanco 5lb',        'sucursal_id' => $sucursalCentral->id, 'categoria_id' => $catAlimentosCentral->id, 'precio_compra' => 2.50, 'precio_venta' => 3.75, 'stock' => 100, 'stock_minimo' => 15, 'unidad_medida' => 'bolsa'],
            ['codigo' => 'PROD-005', 'nombre' => 'Frijoles Negros 1lb',     'sucursal_id' => $sucursalCentral->id, 'categoria_id' => $catAlimentosCentral->id, 'precio_compra' => 1.20, 'precio_venta' => 1.75, 'stock' => 80,  'stock_minimo' => 10, 'unidad_medida' => 'bolsa'],
            ['codigo' => 'PROD-006', 'nombre' => 'Sal Yodada 1kg',          'sucursal_id' => $sucursalCentral->id, 'categoria_id' => $catAlimentosCentral->id, 'precio_compra' => 0.60, 'precio_venta' => 1.00, 'stock' => 60,  'stock_minimo' => 10, 'unidad_medida' => 'bolsa'],
            ['codigo' => 'PROD-007', 'nombre' => 'Detergente en Polvo 1kg', 'sucursal_id' => $sucursalCentral->id, 'categoria_id' => $catLimpiezaCentral->id,  'precio_compra' => 2.00, 'precio_venta' => 3.25, 'stock' => 70,  'stock_minimo' => 10, 'unidad_medida' => 'caja'],
            ['codigo' => 'PROD-008', 'nombre' => 'Cloro Líquido 1L',        'sucursal_id' => $sucursalCentral->id, 'categoria_id' => $catLimpiezaCentral->id,  'precio_compra' => 0.90, 'precio_venta' => 1.50, 'stock' => 90,  'stock_minimo' => 15, 'unidad_medida' => 'unidad'],
            ['codigo' => 'PROD-009', 'nombre' => 'Jabón de Baño Pack x3',   'sucursal_id' => $sucursalCentral->id, 'categoria_id' => $catPersonalCentral->id,  'precio_compra' => 1.50, 'precio_venta' => 2.50, 'stock' => 50,  'stock_minimo' => 8,  'unidad_medida' => 'paquete'],
            ['codigo' => 'PROD-010', 'nombre' => 'Shampoo Grande 700ml',    'sucursal_id' => $sucursalCentral->id, 'categoria_id' => $catPersonalCentral->id,  'precio_compra' => 3.00, 'precio_venta' => 4.75, 'stock' => 40,  'stock_minimo' => 5,  'unidad_medida' => 'unidad'],
        ];

        $productosNorte = [
            ['codigo' => 'PROD-N01', 'nombre' => 'Refresco Tropical 500ml', 'sucursal_id' => $sucursalNorte->id, 'categoria_id' => $catBebidasNorte->id,   'precio_compra' => 0.50, 'precio_venta' => 0.90, 'stock' => 150, 'stock_minimo' => 20, 'unidad_medida' => 'unidad'],
            ['codigo' => 'PROD-N02', 'nombre' => 'Arroz Premium 5lb',       'sucursal_id' => $sucursalNorte->id, 'categoria_id' => $catAlimentosNorte->id, 'precio_compra' => 2.80, 'precio_venta' => 4.00, 'stock' => 80,  'stock_minimo' => 10, 'unidad_medida' => 'bolsa'],
            ['codigo' => 'PROD-N03', 'nombre' => 'Frijoles Rojos 1lb',      'sucursal_id' => $sucursalNorte->id, 'categoria_id' => $catAlimentosNorte->id, 'precio_compra' => 1.10, 'precio_venta' => 1.60, 'stock' => 60,  'stock_minimo' => 10, 'unidad_medida' => 'bolsa'],
        ];

        foreach ($productosCentral as $data) {
            Producto::firstOrCreate(['codigo' => $data['codigo']], $data);
        }
        foreach ($productosNorte as $data) {
            Producto::firstOrCreate(['codigo' => $data['codigo']], $data);
        }

        // Asociar productos a proveedores
        $prod1 = Producto::where('codigo', 'PROD-001')->first();
        $prod2 = Producto::where('codigo', 'PROD-002')->first();
        $prod4 = Producto::where('codigo', 'PROD-004')->first();
        $prod7 = Producto::where('codigo', 'PROD-007')->first();

        if ($prod1 && $prod2) {
            $proveedor1->productos()->syncWithoutDetaching([
                $prod1->id => ['codigo_proveedor' => 'ES-COL-600', 'precio_unitario' => 0.50, 'cantidad_minima' => 24],
                $prod2->id => ['codigo_proveedor' => 'ES-AGU-500', 'precio_unitario' => 0.22, 'cantidad_minima' => 48],
            ]);
        }
        if ($prod4 && $prod7) {
            $proveedor2->productos()->syncWithoutDetaching([
                $prod4->id => ['codigo_proveedor' => 'IN-ARR-5L', 'precio_unitario' => 2.45, 'cantidad_minima' => 10],
                $prod7->id => ['codigo_proveedor' => 'IN-DET-1K', 'precio_unitario' => 1.90, 'cantidad_minima' => 12],
            ]);
        }

        // ── 7. Cobradores por sucursal ────────────────────────────────────────
        $cobrador1Central = Cobrador::firstOrCreate(
            ['email' => 'cobrador1@central.com'],
            [
                'sucursal_id' => $sucursalCentral->id,
                'nombre'      => 'Pedro',
                'apellido'    => 'Mejía',
                'telefono'    => '7111-2222',
                'activo'      => true,
            ]
        );

        $cobrador2Central = Cobrador::firstOrCreate(
            ['email' => 'cobrador2@central.com'],
            [
                'sucursal_id' => $sucursalCentral->id,
                'nombre'      => 'Ana',
                'apellido'    => 'Rivas',
                'telefono'    => '7333-4444',
                'activo'      => true,
            ]
        );

        $cobrador1Norte = Cobrador::firstOrCreate(
            ['email' => 'cobrador1@norte.com'],
            [
                'sucursal_id' => $sucursalNorte->id,
                'nombre'      => 'Luis',
                'apellido'    => 'Hernández',
                'telefono'    => '7555-6666',
                'activo'      => true,
            ]
        );

        // ── 8. Rutas de cobro por sucursal ────────────────────────────────────
        $rutaA = RutaCobro::firstOrCreate(
            ['nombre' => 'Ruta A - Centro', 'sucursal_id' => $sucursalCentral->id],
            [
                'cobrador_id' => $cobrador1Central->id,
                'descripcion' => 'Zona centro de San Salvador',
                'activa'      => true,
            ]
        );

        $rutaB = RutaCobro::firstOrCreate(
            ['nombre' => 'Ruta B - Sur', 'sucursal_id' => $sucursalCentral->id],
            [
                'cobrador_id' => $cobrador2Central->id,
                'descripcion' => 'Zona sur de San Salvador',
                'activa'      => true,
            ]
        );

        $rutaNorte = RutaCobro::firstOrCreate(
            ['nombre' => 'Ruta Norte - Santa Ana', 'sucursal_id' => $sucursalNorte->id],
            [
                'cobrador_id' => $cobrador1Norte->id,
                'descripcion' => 'Zona santa Ana y alrededores',
                'activa'      => true,
            ]
        );

        // ── 9. Vendedores por sucursal ────────────────────────────────────────
        Vendedor::firstOrCreate(
            ['email' => 'vend1@central.com'],
            [
                'sucursal_id' => $sucursalCentral->id,
                'nombre'      => 'Roberto',
                'apellido'    => 'Flores',
                'telefono'    => '7777-8888',
                'activo'      => true,
            ]
        );

        Vendedor::firstOrCreate(
            ['email' => 'vend1@norte.com'],
            [
                'sucursal_id' => $sucursalNorte->id,
                'nombre'      => 'Carmen',
                'apellido'    => 'Gutiérrez',
                'telefono'    => '7999-0000',
                'activo'      => true,
            ]
        );

        // ── 10. Clientes por sucursal ─────────────────────────────────────────
        $clientesCentral = [
            [
                'nombre'         => 'María',
                'apellido'       => 'González',
                'dui'            => '01234567-8',
                'telefono_normal'=> '2100-1011',
                'email'          => 'mgonzalez@mail.com',
                'direccion'      => 'Colonia San Benito, Casa 12',
                'limite_credito' => 500.00,
                'saldo'          => 120.00,
                'ruta_cobro_id'  => $rutaA->id,
                'sucursal_id'    => $sucursalCentral->id,
                'activo'         => true,
            ],
            [
                'nombre'         => 'José',
                'apellido'       => 'Martínez',
                'dui'            => '09876543-2',
                'telefono_normal'=> '2200-2021',
                'email'          => 'jmartinez@mail.com',
                'direccion'      => 'Res. Las Palmas, Apto #5',
                'limite_credito' => 750.00,
                'saldo'          => 0.00,
                'ruta_cobro_id'  => $rutaA->id,
                'sucursal_id'    => $sucursalCentral->id,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Sandra',
                'apellido'       => 'López',
                'dui'            => '05551234-9',
                'telefono_normal'=> '2300-3031',
                'email'          => 'slopez@mail.com',
                'direccion'      => 'Col. Escalón, Av. 51 #14',
                'limite_credito' => 1000.00,
                'saldo'          => 350.50,
                'ruta_cobro_id'  => $rutaB->id,
                'sucursal_id'    => $sucursalCentral->id,
                'activo'         => true,
            ],
        ];

        $clientesNorte = [
            [
                'nombre'         => 'Jorge',
                'apellido'       => 'Reyes',
                'dui'            => '01111111-0',
                'telefono_normal'=> '2401-4041',
                'email'          => 'jreyes@mail.com',
                'direccion'      => 'Colonia Modelo, Santa Ana',
                'limite_credito' => 600.00,
                'saldo'          => 200.00,
                'ruta_cobro_id'  => $rutaNorte->id,
                'sucursal_id'    => $sucursalNorte->id,
                'activo'         => true,
            ],
            [
                'nombre'         => 'Lucía',
                'apellido'       => 'Pérez',
                'dui'            => '02222222-1',
                'telefono_normal'=> '2402-4042',
                'email'          => 'lperez@mail.com',
                'direccion'      => 'Barrio San Miguelito, Santa Ana',
                'limite_credito' => 400.00,
                'saldo'          => 0.00,
                'ruta_cobro_id'  => $rutaNorte->id,
                'sucursal_id'    => $sucursalNorte->id,
                'activo'         => true,
            ],
        ];

        foreach ($clientesCentral as $data) {
            Cliente::firstOrCreate(['dui' => $data['dui']], $data);
        }

        foreach ($clientesNorte as $data) {
            Cliente::firstOrCreate(['dui' => $data['dui']], $data);
        }

        $this->command->info('');
        $this->command->info('✅ TestingSeeder completado exitosamente.');
        $this->command->info('');
        $this->command->info('── Sucursales ──────────────────────────────────────────────');
        $this->command->info("  • Central (ID: {$sucursalCentral->id}) - /administrativo/{$sucursalCentral->id}");
        $this->command->info("  • Norte   (ID: {$sucursalNorte->id}) - /administrativo/{$sucursalNorte->id}");
        $this->command->info('');
        $this->command->info('── Usuarios ────────────────────────────────────────────────');
        $this->command->info('  admin@sidb.com      (super_admin) → accede a Central y Norte');
        $this->command->info('  central@sidb.com    (administrador) → solo Central');
        $this->command->info('  norte@sidb.com      (administrador) → solo Norte');
        $this->command->info('  vendedor@sidb.com   (vendedor)  → solo Central');
        $this->command->info('  Contraseña de todos: password');
        $this->command->info('');
        $this->command->info('── Datos creados ────────────────────────────────────────────');
        $this->command->info('  Categorías: 4 en Central (Bebidas, Alimentos, Limpieza, Personal)');
        $this->command->info('              2 en Norte  (Bebidas, Alimentos)');
        $this->command->info('  Productos: 10 en Central | 3 en Norte');
        $this->command->info('  2 proveedores (globales)');
        $this->command->info('  3 cobradores | 3 rutas de cobro | 2 vendedores');
        $this->command->info('  3 clientes en Central | 2 clientes en Norte');
        $this->command->info('');
    }
}
