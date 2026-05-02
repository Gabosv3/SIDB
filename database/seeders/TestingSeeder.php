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
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin',   'guard_name' => 'web']);
        $roleAdmin      = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $roleVendedor   = Role::firstOrCreate(['name' => 'vendedor',      'guard_name' => 'web']);

        // ── 2. Sucursales ─────────────────────────────────────────────────────
        $central = Sucursal::firstOrCreate(
            ['codigo' => 'CENTRAL'],
            [
                'nombre'    => 'Distribuidora Birancesco Menijvar - Central',
                'direccion' => '1ª Calle Poniente #24, San Salvador',
                'telefono'  => '2222-1111',
                'email'     => 'central@birancesco.com',
                'activo'    => true,
            ]
        );

        $norte = Sucursal::firstOrCreate(
            ['codigo' => 'NORTE'],
            [
                'nombre'    => 'Birancesco - Sucursal Norte',
                'direccion' => 'Av. Independencia #88, Santa Ana',
                'telefono'  => '2444-5555',
                'email'     => 'norte@birancesco.com',
                'activo'    => true,
            ]
        );

        // ── 3. Usuarios ───────────────────────────────────────────────────────
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@sidb.com'],
            ['name' => 'Administrador General', 'password' => Hash::make('password')]
        );
        $superAdmin->syncRoles([$roleSuperAdmin]);
        $superAdmin->sucursales()->syncWithoutDetaching([$central->id, $norte->id]);

        $managerCentral = User::firstOrCreate(
            ['email' => 'central@sidb.com'],
            ['name' => 'Manager Central', 'password' => Hash::make('password')]
        );
        $managerCentral->syncRoles([$roleAdmin]);
        $managerCentral->sucursales()->syncWithoutDetaching([$central->id]);

        $managerNorte = User::firstOrCreate(
            ['email' => 'norte@sidb.com'],
            ['name' => 'Manager Norte', 'password' => Hash::make('password')]
        );
        $managerNorte->syncRoles([$roleAdmin]);
        $managerNorte->sucursales()->syncWithoutDetaching([$norte->id]);

        $vendedorUser = User::firstOrCreate(
            ['email' => 'vendedor@sidb.com'],
            ['name' => 'Juan Vendedor', 'password' => Hash::make('password')]
        );
        $vendedorUser->syncRoles([$roleVendedor]);
        $vendedorUser->sucursales()->syncWithoutDetaching([$central->id]);

        // ── 4. Categorías ─────────────────────────────────────────────────────
        Categoria::whereNull('sucursal_id')->delete();

        $nombresCateg = [
            'Muebles de Sala'          => 'Sofás, sillones, mesas de centro y estantes para sala.',
            'Muebles de Comedor'       => 'Mesas de comedor, sillas y aparadores.',
            'Muebles de Dormitorio'    => 'Camas, roperos, cómodas y mesas de noche.',
            'Electrodomésticos'        => 'Refrigeradoras, microondas, lavadoras y televisores.',
            'Ventiladores y Climatiz.' => 'Ventiladores de mesa, pie y techo. Aires acondicionados.',
            'Iluminación'              => 'Lámparas de pie, techo, veladores y focos LED.',
            'Colchones'                => 'Colchones individuales, matrimoniales y queen size.',
            'Decoración'               => 'Cuadros, espejos decorativos, floreros y objetos de adorno.',
        ];

        $catsCentral = [];
        $catsNorte   = [];

        foreach ($nombresCateg as $nombre => $desc) {
            $catsCentral[$nombre] = Categoria::firstOrCreate(
                ['nombre' => $nombre, 'sucursal_id' => $central->id],
                ['descripcion' => $desc, 'activo' => true]
            );
            $catsNorte[$nombre] = Categoria::firstOrCreate(
                ['nombre' => $nombre, 'sucursal_id' => $norte->id],
                ['descripcion' => $desc, 'activo' => true]
            );
        }

        // ── 5. Proveedores ────────────────────────────────────────────────────
        $provMuebles = Proveedor::firstOrCreate(
            ['codigo' => 'PROV-001'],
            [
                'nombre'             => 'Muebles y Maderas El Roble S.A.',
                'contacto_principal' => 'Carlos Ramírez',
                'email'              => 'ventas@elroble.com.sv',
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

        $provElectro = Proveedor::firstOrCreate(
            ['codigo' => 'PROV-002'],
            [
                'nombre'             => 'Importadora TechHome LTDA',
                'contacto_principal' => 'María López',
                'email'              => 'pedidos@techhome.com.sv',
                'telefono'           => '2266-7788',
                'direccion'          => 'Blvd. Constitución #200, Local 4',
                'ciudad'             => 'San Salvador',
                'departamento'       => 'San Salvador',
                'pais'               => 'El Salvador',
                'condiciones_pago'   => 'credito',
                'dias_credito'       => 15,
                'activo'             => true,
            ]
        );

        $provColchones = Proveedor::firstOrCreate(
            ['codigo' => 'PROV-003'],
            [
                'nombre'             => 'Colchones Comfort S.A. de C.V.',
                'contacto_principal' => 'Roberto Salinas',
                'email'              => 'ventas@comfort.com.sv',
                'telefono'           => '2288-9900',
                'direccion'          => 'Carretera Panamericana Km. 9',
                'ciudad'             => 'Antiguo Cuscatlán',
                'departamento'       => 'La Libertad',
                'pais'               => 'El Salvador',
                'condiciones_pago'   => 'credito',
                'dias_credito'       => 45,
                'activo'             => true,
            ]
        );

        // ── 6. Productos CENTRAL ──────────────────────────────────────────────
        $productosCentral = [
            // Muebles de Sala
            [
                'codigo'         => 'PROD-001',
                'nombre'         => 'Sofá 3 Puestos Madrid',
                'descripcion'    => 'Sofá tapizado en tela microfibra color gris, estructura de madera sólida, cojines removibles.',
                'categoria_id'   => $catsCentral['Muebles de Sala']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 280.00,
                'precio_venta'   => 450.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 78.00], ['cuotas' => 12, 'precio' => 40.00], ['cuotas' => 24, 'precio' => 22.00]],
                'stock'          => 12,
                'stock_minimo'   => 3,
                'peso'           => 42.500,
                'dimensiones'    => '200x85x90 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-002',
                'nombre'         => 'Sillón Individual Reclinable',
                'descripcion'    => 'Sillón reclinable de 3 posiciones, tapiz en polipiel negro, patas de madera.',
                'categoria_id'   => $catsCentral['Muebles de Sala']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 130.00,
                'precio_venta'   => 215.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 37.00], ['cuotas' => 12, 'precio' => 19.50]],
                'stock'          => 18,
                'stock_minimo'   => 4,
                'peso'           => 22.000,
                'dimensiones'    => '85x90x105 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-003',
                'nombre'         => 'Mesa de Centro de Vidrio',
                'descripcion'    => 'Mesa de centro con tapa de vidrio templado 8mm y base de metal negro.',
                'categoria_id'   => $catsCentral['Muebles de Sala']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 60.00,
                'precio_venta'   => 95.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 33.00], ['cuotas' => 6, 'precio' => 17.00]],
                'stock'          => 20,
                'stock_minimo'   => 5,
                'peso'           => 14.200,
                'dimensiones'    => '110x60x45 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-004',
                'nombre'         => 'Estante Librero 5 Repisas',
                'descripcion'    => 'Librero de madera MDF laqueado blanco, 5 repisas regulables, ideal para sala u oficina.',
                'categoria_id'   => $catsCentral['Muebles de Sala']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 75.00,
                'precio_venta'   => 120.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 42.00], ['cuotas' => 6, 'precio' => 22.00]],
                'stock'          => 15,
                'stock_minimo'   => 3,
                'peso'           => 28.000,
                'dimensiones'    => '80x30x180 cm',
                'activo'         => true,
            ],
            // Muebles de Comedor
            [
                'codigo'         => 'PROD-005',
                'nombre'         => 'Juego Comedor 6 Sillas',
                'descripcion'    => 'Mesa rectangular de madera sólida con 6 sillas tapizadas, color nogal, capacidad 6 personas.',
                'categoria_id'   => $catsCentral['Muebles de Comedor']->id,
                'unidad_medida'  => 'conjunto',
                'precio_compra'  => 350.00,
                'precio_venta'   => 570.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 98.00], ['cuotas' => 12, 'precio' => 51.00], ['cuotas' => 24, 'precio' => 27.00]],
                'stock'          => 8,
                'stock_minimo'   => 2,
                'peso'           => 68.000,
                'dimensiones'    => '160x90x76 cm (mesa)',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-006',
                'nombre'         => 'Silla de Comedor Tapizada',
                'descripcion'    => 'Silla individual para comedor, asiento tapizado en tela caqui, respaldo de madera.',
                'categoria_id'   => $catsCentral['Muebles de Comedor']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 22.00,
                'precio_venta'   => 38.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 13.50]],
                'stock'          => 60,
                'stock_minimo'   => 12,
                'peso'           => 5.500,
                'dimensiones'    => '42x46x95 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-007',
                'nombre'         => 'Aparador / Buffet 3 Puertas',
                'descripcion'    => 'Aparador de madera maciza, 3 puertas con bisagras suaves, 2 cajones, color wengué.',
                'categoria_id'   => $catsCentral['Muebles de Comedor']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 160.00,
                'precio_venta'   => 265.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 46.00], ['cuotas' => 12, 'precio' => 24.00]],
                'stock'          => 10,
                'stock_minimo'   => 2,
                'peso'           => 38.000,
                'dimensiones'    => '150x45x85 cm',
                'activo'         => true,
            ],
            // Muebles de Dormitorio
            [
                'codigo'         => 'PROD-008',
                'nombre'         => 'Cama Queen con Cabecera',
                'descripcion'    => 'Cama queen (160x200 cm) con cabecera tapizada en ecocuero beige, base de madera maciza.',
                'categoria_id'   => $catsCentral['Muebles de Dormitorio']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 220.00,
                'precio_venta'   => 360.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 62.00], ['cuotas' => 12, 'precio' => 32.50], ['cuotas' => 24, 'precio' => 17.50]],
                'stock'          => 10,
                'stock_minimo'   => 2,
                'peso'           => 55.000,
                'dimensiones'    => '160x200x110 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-009',
                'nombre'         => 'Ropero 3 Puertas con Espejo',
                'descripcion'    => 'Ropero de melamina blanco, 3 puertas corredizas (1 espejo), 4 cajones internos.',
                'categoria_id'   => $catsCentral['Muebles de Dormitorio']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 290.00,
                'precio_venta'   => 480.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 83.00], ['cuotas' => 12, 'precio' => 43.00], ['cuotas' => 24, 'precio' => 23.00]],
                'stock'          => 7,
                'stock_minimo'   => 2,
                'peso'           => 72.000,
                'dimensiones'    => '180x60x200 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-010',
                'nombre'         => 'Mesa de Noche 2 Cajones',
                'descripcion'    => 'Mesa de noche de MDF con 2 cajones y 1 estante, color cerezo.',
                'categoria_id'   => $catsCentral['Muebles de Dormitorio']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 45.00,
                'precio_venta'   => 75.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 26.50]],
                'stock'          => 25,
                'stock_minimo'   => 5,
                'peso'           => 12.000,
                'dimensiones'    => '50x40x60 cm',
                'activo'         => true,
            ],
            // Electrodomésticos
            [
                'codigo'         => 'PROD-011',
                'nombre'         => 'Televisor Smart TV 43" 4K',
                'descripcion'    => 'TV Smart 43 pulgadas, 4K UHD, WiFi, Android TV, HDMI x3, USB x2.',
                'categoria_id'   => $catsCentral['Electrodomésticos']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 320.00,
                'precio_venta'   => 520.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 90.00], ['cuotas' => 12, 'precio' => 47.00], ['cuotas' => 24, 'precio' => 25.00]],
                'stock'          => 14,
                'stock_minimo'   => 3,
                'peso'           => 8.200,
                'dimensiones'    => '97x57x8 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-012',
                'nombre'         => 'Refrigeradora Side by Side 18 Pies',
                'descripcion'    => 'Refrigeradora doble puerta, 18 pies cúbicos, dispensador de agua y hielo, color plateado.',
                'categoria_id'   => $catsCentral['Electrodomésticos']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 680.00,
                'precio_venta'   => 1100.00,
                'precios_cuotas' => [['cuotas' => 12, 'precio' => 97.00], ['cuotas' => 24, 'precio' => 51.00], ['cuotas' => 36, 'precio' => 36.00]],
                'stock'          => 6,
                'stock_minimo'   => 2,
                'peso'           => 88.000,
                'dimensiones'    => '91x71x178 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-013',
                'nombre'         => 'Lavadora Automática 18 lb',
                'descripcion'    => 'Lavadora de carga superior, 18 libras, 12 programas, eficiencia A+, color blanco.',
                'categoria_id'   => $catsCentral['Electrodomésticos']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 310.00,
                'precio_venta'   => 510.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 88.00], ['cuotas' => 12, 'precio' => 46.00], ['cuotas' => 24, 'precio' => 25.00]],
                'stock'          => 9,
                'stock_minimo'   => 2,
                'peso'           => 38.500,
                'dimensiones'    => '55x55x100 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-014',
                'nombre'         => 'Microondas Digital 0.9 Pies',
                'descripcion'    => 'Microondas 0.9 pies, 700W, panel digital, 5 niveles de potencia, color negro.',
                'categoria_id'   => $catsCentral['Electrodomésticos']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 65.00,
                'precio_venta'   => 105.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 37.00], ['cuotas' => 6, 'precio' => 19.50]],
                'stock'          => 22,
                'stock_minimo'   => 5,
                'peso'           => 11.000,
                'dimensiones'    => '44x32x26 cm',
                'activo'         => true,
            ],
            // Ventiladores y Climatización
            [
                'codigo'         => 'PROD-015',
                'nombre'         => 'Ventilador de Pie 16" Cromado',
                'descripcion'    => 'Ventilador de pie, 3 velocidades, 16 pulgadas, cabezal oscilante, base cromada, silencioso.',
                'categoria_id'   => $catsCentral['Ventiladores y Climatiz.']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 28.00,
                'precio_venta'   => 48.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 17.00]],
                'stock'          => 35,
                'stock_minimo'   => 8,
                'peso'           => 4.800,
                'dimensiones'    => '40x40x130 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-016',
                'nombre'         => 'Ventilador de Techo 52" con Luz',
                'descripcion'    => 'Ventilador de techo, 52 pulgadas, kit LED integrado, 3 velocidades, control remoto.',
                'categoria_id'   => $catsCentral['Ventiladores y Climatiz.']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 75.00,
                'precio_venta'   => 125.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 44.00], ['cuotas' => 6, 'precio' => 23.00]],
                'stock'          => 20,
                'stock_minimo'   => 4,
                'peso'           => 6.500,
                'dimensiones'    => 'Diámetro 132 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-017',
                'nombre'         => 'Ventilador de Mesa 12"',
                'descripcion'    => 'Ventilador de sobremesa, 12 pulgadas, 2 velocidades, cabezal ligero, ideal para escritorio.',
                'categoria_id'   => $catsCentral['Ventiladores y Climatiz.']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 15.00,
                'precio_venta'   => 25.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 9.00]],
                'stock'          => 50,
                'stock_minimo'   => 10,
                'peso'           => 2.100,
                'dimensiones'    => '30x30x38 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-018',
                'nombre'         => 'Aire Acondicionado Split 12,000 BTU',
                'descripcion'    => 'Minisplit frío/calor, 12,000 BTU, inverter, control remoto, temporizador, color blanco.',
                'categoria_id'   => $catsCentral['Ventiladores y Climatiz.']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 420.00,
                'precio_venta'   => 680.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 118.00], ['cuotas' => 12, 'precio' => 61.00], ['cuotas' => 24, 'precio' => 33.00]],
                'stock'          => 8,
                'stock_minimo'   => 2,
                'peso'           => 28.000,
                'dimensiones'    => '85x20x30 cm (unidad interior)',
                'activo'         => true,
            ],
            // Iluminación
            [
                'codigo'         => 'PROD-019',
                'nombre'         => 'Lámpara de Pie Trípode',
                'descripcion'    => 'Lámpara de pie nórdica, base trípode madera, pantalla de lino crema, casquillo E27.',
                'categoria_id'   => $catsCentral['Iluminación']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 40.00,
                'precio_venta'   => 68.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 24.00]],
                'stock'          => 18,
                'stock_minimo'   => 4,
                'peso'           => 3.200,
                'dimensiones'    => 'Diám. 35x155 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-020',
                'nombre'         => 'Kit 6 Focos LED 9W E27',
                'descripcion'    => 'Pack de 6 focos LED, 9W (equiv. 60W), luz cálida 3000K, base E27, vida útil 25,000 h.',
                'categoria_id'   => $catsCentral['Iluminación']->id,
                'unidad_medida'  => 'paquete',
                'precio_compra'  => 7.00,
                'precio_venta'   => 12.00,
                'precios_cuotas' => null,
                'stock'          => 100,
                'stock_minimo'   => 20,
                'peso'           => 0.540,
                'dimensiones'    => null,
                'activo'         => true,
            ],
            // Colchones
            [
                'codigo'         => 'PROD-021',
                'nombre'         => 'Colchón Queen Ortopédico',
                'descripcion'    => 'Colchón queen (160x200 cm), resortes ensacados, memory foam 5 cm, tela antialérgica.',
                'categoria_id'   => $catsCentral['Colchones']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 260.00,
                'precio_venta'   => 420.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 73.00], ['cuotas' => 12, 'precio' => 38.00], ['cuotas' => 24, 'precio' => 20.50]],
                'stock'          => 10,
                'stock_minimo'   => 2,
                'peso'           => 32.000,
                'dimensiones'    => '160x200x28 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-022',
                'nombre'         => 'Colchón Individual Espuma Alta Densidad',
                'descripcion'    => 'Colchón individual (90x190 cm), espuma 28 kg/m³, funda removible lavable.',
                'categoria_id'   => $catsCentral['Colchones']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 90.00,
                'precio_venta'   => 150.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 53.00], ['cuotas' => 6, 'precio' => 28.00]],
                'stock'          => 18,
                'stock_minimo'   => 4,
                'peso'           => 14.500,
                'dimensiones'    => '90x190x18 cm',
                'activo'         => true,
            ],
            // Decoración
            [
                'codigo'         => 'PROD-023',
                'nombre'         => 'Espejo Decorativo Redondo 60cm',
                'descripcion'    => 'Espejo redondo de pared, marco metálico negro mate, diámetro 60 cm.',
                'categoria_id'   => $catsCentral['Decoración']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 25.00,
                'precio_venta'   => 42.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 15.00]],
                'stock'          => 25,
                'stock_minimo'   => 5,
                'peso'           => 3.800,
                'dimensiones'    => 'Diám. 60 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-024',
                'nombre'         => 'Cuadro Canvas Abstracto 60x80 cm',
                'descripcion'    => 'Cuadro decorativo impresión canvas, colores modernos, bastidor de madera, listo para colgar.',
                'categoria_id'   => $catsCentral['Decoración']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 18.00,
                'precio_venta'   => 32.00,
                'precios_cuotas' => null,
                'stock'          => 30,
                'stock_minimo'   => 6,
                'peso'           => 1.200,
                'dimensiones'    => '60x80 cm',
                'activo'         => true,
            ],
        ];

        foreach ($productosCentral as $data) {
            $data['sucursal_id'] = $central->id;
            Producto::firstOrCreate(['codigo' => $data['codigo']], $data);
        }

        // ── 7. Productos NORTE ────────────────────────────────────────────────
        $productosNorte = [
            [
                'codigo'         => 'PROD-N01',
                'nombre'         => 'Sofá 2 Puestos Lisboa',
                'descripcion'    => 'Sofá compacto 2 puestos, tela borreguillo beige, patas metálicas, cojines incluidos.',
                'categoria_id'   => $catsNorte['Muebles de Sala']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 200.00,
                'precio_venta'   => 330.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 57.00], ['cuotas' => 12, 'precio' => 30.00]],
                'stock'          => 8,
                'stock_minimo'   => 2,
                'peso'           => 32.000,
                'dimensiones'    => '150x80x88 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-N02',
                'nombre'         => 'Juego Comedor 4 Sillas Madera',
                'descripcion'    => 'Mesa cuadrada de pino, 4 sillas con asiento tapizado, color natural barnizado.',
                'categoria_id'   => $catsNorte['Muebles de Comedor']->id,
                'unidad_medida'  => 'conjunto',
                'precio_compra'  => 240.00,
                'precio_venta'   => 390.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 67.00], ['cuotas' => 12, 'precio' => 35.00]],
                'stock'          => 6,
                'stock_minimo'   => 2,
                'peso'           => 52.000,
                'dimensiones'    => '90x90x76 cm (mesa)',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-N03',
                'nombre'         => 'Ventilador de Pie 16" Negro',
                'descripcion'    => 'Ventilador de pie, 16 pulgadas, 3 velocidades, motor silencioso, acabado negro mate.',
                'categoria_id'   => $catsNorte['Ventiladores y Climatiz.']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 27.00,
                'precio_venta'   => 46.00,
                'precios_cuotas' => [['cuotas' => 3, 'precio' => 16.50]],
                'stock'          => 28,
                'stock_minimo'   => 6,
                'peso'           => 4.600,
                'dimensiones'    => '40x40x130 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-N04',
                'nombre'         => 'Televisor Smart TV 32" HD',
                'descripcion'    => 'TV Smart 32 pulgadas, HD 1366x768, WiFi, Android TV, 2 HDMI.',
                'categoria_id'   => $catsNorte['Electrodomésticos']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 170.00,
                'precio_venta'   => 280.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 48.50], ['cuotas' => 12, 'precio' => 25.50]],
                'stock'          => 12,
                'stock_minimo'   => 3,
                'peso'           => 4.800,
                'dimensiones'    => '73x43x8 cm',
                'activo'         => true,
            ],
            [
                'codigo'         => 'PROD-N05',
                'nombre'         => 'Colchón Matrimonial Resortes',
                'descripcion'    => 'Colchón matrimonial (135x190 cm), resortes Bonnell, acolchado pillow top.',
                'categoria_id'   => $catsNorte['Colchones']->id,
                'unidad_medida'  => 'unidad',
                'precio_compra'  => 175.00,
                'precio_venta'   => 290.00,
                'precios_cuotas' => [['cuotas' => 6, 'precio' => 50.50], ['cuotas' => 12, 'precio' => 26.50]],
                'stock'          => 8,
                'stock_minimo'   => 2,
                'peso'           => 25.000,
                'dimensiones'    => '135x190x24 cm',
                'activo'         => true,
            ],
        ];

        foreach ($productosNorte as $data) {
            $data['sucursal_id'] = $norte->id;
            Producto::firstOrCreate(['codigo' => $data['codigo']], $data);
        }

        // ── 8. Asociar productos a proveedores ────────────────────────────────
        $syncMuebles = [];
        foreach (Producto::whereIn('codigo', ['PROD-001','PROD-002','PROD-003','PROD-004','PROD-005','PROD-006','PROD-007','PROD-008','PROD-009','PROD-010'])->get() as $p) {
            $syncMuebles[$p->id] = ['codigo_proveedor' => 'ROBLE-'.$p->codigo, 'precio_unitario' => round($p->precio_compra * 0.95, 2), 'cantidad_minima' => 2];
        }
        if ($syncMuebles) $provMuebles->productos()->syncWithoutDetaching($syncMuebles);

        $syncElectro = [];
        foreach (Producto::whereIn('codigo', ['PROD-011','PROD-012','PROD-013','PROD-014','PROD-015','PROD-016','PROD-017','PROD-018','PROD-019','PROD-020'])->get() as $p) {
            $syncElectro[$p->id] = ['codigo_proveedor' => 'TH-'.$p->codigo, 'precio_unitario' => round($p->precio_compra * 0.95, 2), 'cantidad_minima' => 1];
        }
        if ($syncElectro) $provElectro->productos()->syncWithoutDetaching($syncElectro);

        $syncColchon = [];
        foreach (Producto::whereIn('codigo', ['PROD-021','PROD-022'])->get() as $p) {
            $syncColchon[$p->id] = ['codigo_proveedor' => 'CF-'.$p->codigo, 'precio_unitario' => round($p->precio_compra * 0.95, 2), 'cantidad_minima' => 1];
        }
        if ($syncColchon) $provColchones->productos()->syncWithoutDetaching($syncColchon);

        // ── 9. Cobradores ─────────────────────────────────────────────────────
        $cob1 = Cobrador::firstOrCreate(
            ['email' => 'cobrador1@birancesco.com'],
            ['sucursal_id' => $central->id, 'nombre' => 'Pedro', 'apellido' => 'Mejía', 'telefono' => '7111-2222', 'activo' => true]
        );
        $cob2 = Cobrador::firstOrCreate(
            ['email' => 'cobrador2@birancesco.com'],
            ['sucursal_id' => $central->id, 'nombre' => 'Ana', 'apellido' => 'Rivas', 'telefono' => '7333-4444', 'activo' => true]
        );
        $cob3 = Cobrador::firstOrCreate(
            ['email' => 'cobrador3@birancesco.com'],
            ['sucursal_id' => $central->id, 'nombre' => 'Oscar', 'apellido' => 'Fuentes', 'telefono' => '7555-1122', 'activo' => true]
        );
        $cobNorte = Cobrador::firstOrCreate(
            ['email' => 'cobrador1.norte@birancesco.com'],
            ['sucursal_id' => $norte->id, 'nombre' => 'Luis', 'apellido' => 'Hernández', 'telefono' => '7666-7788', 'activo' => true]
        );

        // ── 10. Rutas de cobro ────────────────────────────────────────────────
        $rutaCentro = RutaCobro::firstOrCreate(
            ['nombre' => 'Ruta Centro - SS', 'sucursal_id' => $central->id],
            ['cobrador_id' => $cob1->id, 'descripcion' => 'Centro de San Salvador y alrededores', 'activa' => true]
        );
        $rutaSur = RutaCobro::firstOrCreate(
            ['nombre' => 'Ruta Sur - SS', 'sucursal_id' => $central->id],
            ['cobrador_id' => $cob2->id, 'descripcion' => 'Zona sur: Soyapango, Ilopango, San Martín', 'activa' => true]
        );
        $rutaOriente = RutaCobro::firstOrCreate(
            ['nombre' => 'Ruta Oriente - SS', 'sucursal_id' => $central->id],
            ['cobrador_id' => $cob3->id, 'descripcion' => 'Zona oriente: Apopa, Ciudad Delgado', 'activa' => true]
        );
        $rutaNorteRuta = RutaCobro::firstOrCreate(
            ['nombre' => 'Ruta Santa Ana Urbano', 'sucursal_id' => $norte->id],
            ['cobrador_id' => $cobNorte->id, 'descripcion' => 'Santa Ana ciudad y Chalchuapa', 'activa' => true]
        );

        // ── 11. Vendedores ────────────────────────────────────────────────────
        Vendedor::firstOrCreate(
            ['email' => 'vend1@birancesco.com'],
            ['sucursal_id' => $central->id, 'nombre' => 'Roberto', 'apellido' => 'Flores', 'telefono' => '7777-8888', 'activo' => true]
        );
        Vendedor::firstOrCreate(
            ['email' => 'vend2@birancesco.com'],
            ['sucursal_id' => $central->id, 'nombre' => 'Karla', 'apellido' => 'Morales', 'telefono' => '7888-9999', 'activo' => true]
        );
        Vendedor::firstOrCreate(
            ['email' => 'vend1.norte@birancesco.com'],
            ['sucursal_id' => $norte->id, 'nombre' => 'Carmen', 'apellido' => 'Gutiérrez', 'telefono' => '7999-0011', 'activo' => true]
        );

        // ── 12. Clientes CENTRAL ──────────────────────────────────────────────
        $clientesCentral = [
            [
                'nombre'              => 'María',
                'apellido'            => 'González de Pérez',
                'nombre_conyuge'      => 'Carlos Pérez',
                'dui'                 => '01234567-8',
                'nit'                 => '0614-010185-001-5',
                'telefono_normal'     => '2100-1010',
                'telefono_whatsapp'   => '7100-1010',
                'email'               => 'mgonzalez@gmail.com',
                'direccion'           => 'Colonia San Benito, Calle Las Palmas #12',
                'departamento'        => 'San Salvador',
                'municipio'           => 'San Salvador',
                'distrito'            => 'Centro',
                'latitud'             => '13.69294',
                'longitud'            => '-89.21819',
                'limite_credito'      => 1500.00,
                'saldo'               => 350.00,
                'ruta_cobro_id'       => $rutaCentro->id,
                'activo'              => true,
                'ref_fam1_nombre'     => 'Rosa González',
                'ref_fam1_telefono'   => '7200-1122',
                'ref_fam1_parentesco' => 'Hermana',
                'ref_fam2_nombre'     => 'Carlos Pérez',
                'ref_fam2_telefono'   => '7300-3344',
                'ref_fam2_parentesco' => 'Esposo',
                'ref_con1_nombre'     => 'Lucía Hernández',
                'ref_con1_telefono'   => '7400-4455',
                'ref_con1_trabajo'    => 'Maestra - Escuela San José',
                'ref_con2_nombre'     => 'Mario Fuentes',
                'ref_con2_telefono'   => '7500-5566',
                'ref_con2_trabajo'    => 'Mecánico independiente',
                'sucursal_id'         => $central->id,
            ],
            [
                'nombre'              => 'José',
                'apellido'            => 'Martínez Ramos',
                'nombre_conyuge'      => 'Ana Sofía Ramos',
                'dui'                 => '09876543-2',
                'nit'                 => '0614-150390-002-1',
                'telefono_normal'     => '2200-2020',
                'telefono_whatsapp'   => '7200-2020',
                'email'               => 'jmartinez@hotmail.com',
                'direccion'           => 'Residencial Las Palmas, Bloc A, Apto 5',
                'departamento'        => 'San Salvador',
                'municipio'           => 'Ilopango',
                'distrito'            => 'Ilopango',
                'latitud'             => '13.70056',
                'longitud'            => '-89.11400',
                'limite_credito'      => 2000.00,
                'saldo'               => 0.00,
                'ruta_cobro_id'       => $rutaCentro->id,
                'activo'              => true,
                'ref_fam1_nombre'     => 'Luis Martínez',
                'ref_fam1_telefono'   => '7210-2233',
                'ref_fam1_parentesco' => 'Hermano',
                'ref_fam2_nombre'     => 'Elena Ramos',
                'ref_fam2_telefono'   => '7310-3344',
                'ref_fam2_parentesco' => 'Suegra',
                'ref_con1_nombre'     => 'Pedro Villalta',
                'ref_con1_telefono'   => '7410-4455',
                'ref_con1_trabajo'    => 'Empleado - Almacén El Cóndor',
                'ref_con2_nombre'     => 'Sandra Díaz',
                'ref_con2_telefono'   => '7510-5566',
                'ref_con2_trabajo'    => 'Enfermera - Hospital Bloom',
                'sucursal_id'         => $central->id,
            ],
            [
                'nombre'              => 'Sandra',
                'apellido'            => 'López Orellana',
                'nombre_conyuge'      => null,
                'dui'                 => '05551234-9',
                'nit'                 => '0614-220878-003-8',
                'telefono_normal'     => '2300-3030',
                'telefono_whatsapp'   => '7300-3030',
                'email'               => 'slopez@yahoo.com',
                'direccion'           => 'Colonia Escalón, 51 Av. Norte #14',
                'departamento'        => 'San Salvador',
                'municipio'           => 'San Salvador',
                'distrito'            => 'Escalón',
                'latitud'             => '13.70600',
                'longitud'            => '-89.23200',
                'limite_credito'      => 3000.00,
                'saldo'               => 850.50,
                'ruta_cobro_id'       => $rutaSur->id,
                'activo'              => true,
                'ref_fam1_nombre'     => 'Teresa López',
                'ref_fam1_telefono'   => '7320-3322',
                'ref_fam1_parentesco' => 'Madre',
                'ref_fam2_nombre'     => 'Roberto López',
                'ref_fam2_telefono'   => '7321-3344',
                'ref_fam2_parentesco' => 'Hermano',
                'ref_con1_nombre'     => 'Fernanda Vásquez',
                'ref_con1_telefono'   => '7421-4455',
                'ref_con1_trabajo'    => 'Contadora - Despacho Ramírez',
                'ref_con2_nombre'     => 'Hugo Castillo',
                'ref_con2_telefono'   => '7521-5566',
                'ref_con2_trabajo'    => 'Propietario - Ferretería Castillo',
                'sucursal_id'         => $central->id,
            ],
            [
                'nombre'              => 'Francisco',
                'apellido'            => 'Ramírez Molina',
                'nombre_conyuge'      => 'Claudia Molina',
                'dui'                 => '03339876-1',
                'nit'                 => '0614-050775-004-2',
                'telefono_normal'     => '2400-4040',
                'telefono_whatsapp'   => '7400-4040',
                'email'               => 'framirez@gmail.com',
                'direccion'           => 'Colonia Modelo, Pasaje 3, Casa 7, Soyapango',
                'departamento'        => 'San Salvador',
                'municipio'           => 'Soyapango',
                'distrito'            => 'Soyapango Norte',
                'latitud'             => '13.71100',
                'longitud'            => '-89.15000',
                'limite_credito'      => 1200.00,
                'saldo'               => 200.00,
                'ruta_cobro_id'       => $rutaSur->id,
                'activo'              => true,
                'ref_fam1_nombre'     => 'Marco Ramírez',
                'ref_fam1_telefono'   => '7430-4433',
                'ref_fam1_parentesco' => 'Padre',
                'ref_fam2_nombre'     => 'Claudia Molina',
                'ref_fam2_telefono'   => '7431-4455',
                'ref_fam2_parentesco' => 'Esposa',
                'ref_con1_nombre'     => 'Oscar Portillo',
                'ref_con1_telefono'   => '7531-5566',
                'ref_con1_trabajo'    => 'Guardia de seguridad - Grupo Taca',
                'ref_con2_nombre'     => 'Iris Mejía',
                'ref_con2_telefono'   => '7631-6677',
                'ref_con2_trabajo'    => 'Estilista - Salón Beauty',
                'sucursal_id'         => $central->id,
            ],
            [
                'nombre'              => 'Elena',
                'apellido'            => 'Vásquez de Flores',
                'nombre_conyuge'      => 'Miguel Flores',
                'dui'                 => '07771234-5',
                'nit'                 => '0614-301292-005-9',
                'telefono_normal'     => '2500-5050',
                'telefono_whatsapp'   => '7500-5050',
                'email'               => 'evasquez@outlook.com',
                'direccion'           => 'Urb. Santa Fe, Calle Principal #45, Ciudad Delgado',
                'departamento'        => 'San Salvador',
                'municipio'           => 'Ciudad Delgado',
                'distrito'            => 'Ciudad Delgado',
                'latitud'             => '13.72500',
                'longitud'            => '-89.16200',
                'limite_credito'      => 2500.00,
                'saldo'               => 670.00,
                'ruta_cobro_id'       => $rutaOriente->id,
                'activo'              => true,
                'ref_fam1_nombre'     => 'Silvia Vásquez',
                'ref_fam1_telefono'   => '7540-5544',
                'ref_fam1_parentesco' => 'Hermana',
                'ref_fam2_nombre'     => 'Miguel Flores',
                'ref_fam2_telefono'   => '7541-5566',
                'ref_fam2_parentesco' => 'Esposo',
                'ref_con1_nombre'     => 'Patricia Landaverde',
                'ref_con1_telefono'   => '7641-6677',
                'ref_con1_trabajo'    => 'Empleada - Alcaldía Ciudad Delgado',
                'ref_con2_nombre'     => 'Ernesto Sánchez',
                'ref_con2_telefono'   => '7741-7788',
                'ref_con2_trabajo'    => 'Electricista independiente',
                'sucursal_id'         => $central->id,
            ],
            [
                'nombre'              => 'Roberto',
                'apellido'            => 'Hernández Cruz',
                'nombre_conyuge'      => null,
                'dui'                 => '04445678-3',
                'nit'                 => '0614-140288-006-6',
                'telefono_normal'     => '2600-6060',
                'telefono_whatsapp'   => '7600-6060',
                'email'               => 'rhcruz@gmail.com',
                'direccion'           => 'Comunidad 10 de Octubre, Casa 22, Apopa',
                'departamento'        => 'San Salvador',
                'municipio'           => 'Apopa',
                'distrito'            => 'Apopa',
                'latitud'             => '13.79200',
                'longitud'            => '-89.17800',
                'limite_credito'      => 800.00,
                'saldo'               => 125.00,
                'ruta_cobro_id'       => $rutaOriente->id,
                'activo'              => true,
                'ref_fam1_nombre'     => 'Julia Cruz',
                'ref_fam1_telefono'   => '7610-6644',
                'ref_fam1_parentesco' => 'Madre',
                'ref_fam2_nombre'     => 'Danilo Hernández',
                'ref_fam2_telefono'   => '7611-6655',
                'ref_fam2_parentesco' => 'Hermano',
                'ref_con1_nombre'     => 'Rolando Aguilar',
                'ref_con1_telefono'   => '7711-7766',
                'ref_con1_trabajo'    => 'Empleado - Mercado Central',
                'ref_con2_nombre'     => 'Beatriz Melara',
                'ref_con2_telefono'   => '7811-8877',
                'ref_con2_trabajo'    => 'Costurera independiente',
                'sucursal_id'         => $central->id,
            ],
        ];

        // ── 13. Clientes NORTE ────────────────────────────────────────────────
        $clientesNorte = [
            [
                'nombre'              => 'Jorge',
                'apellido'            => 'Reyes Interiano',
                'nombre_conyuge'      => 'Marisol Interiano',
                'dui'                 => '01111111-0',
                'nit'                 => '0801-120183-007-3',
                'telefono_normal'     => '2441-1010',
                'telefono_whatsapp'   => '7441-1010',
                'email'               => 'jreyes@gmail.com',
                'direccion'           => 'Col. Modelo, 4ª Calle Ote. #18, Santa Ana',
                'departamento'        => 'Santa Ana',
                'municipio'           => 'Santa Ana',
                'distrito'            => 'Santa Ana Centro',
                'latitud'             => '13.99900',
                'longitud'            => '-89.55900',
                'limite_credito'      => 1800.00,
                'saldo'               => 400.00,
                'ruta_cobro_id'       => $rutaNorteRuta->id,
                'activo'              => true,
                'ref_fam1_nombre'     => 'Carlos Reyes',
                'ref_fam1_telefono'   => '7451-1122',
                'ref_fam1_parentesco' => 'Hermano',
                'ref_fam2_nombre'     => 'Marisol Interiano',
                'ref_fam2_telefono'   => '7452-2233',
                'ref_fam2_parentesco' => 'Esposa',
                'ref_con1_nombre'     => 'Alfredo Molina',
                'ref_con1_telefono'   => '7552-3344',
                'ref_con1_trabajo'    => 'Docente - Instituto Nacional Santa Ana',
                'ref_con2_nombre'     => 'Sara Peña',
                'ref_con2_telefono'   => '7652-4455',
                'ref_con2_trabajo'    => 'Administradora - Hotel Santa Ana',
                'sucursal_id'         => $norte->id,
            ],
            [
                'nombre'              => 'Lucía',
                'apellido'            => 'Pérez Monterrosa',
                'nombre_conyuge'      => null,
                'dui'                 => '02222222-1',
                'nit'                 => '0801-070595-008-0',
                'telefono_normal'     => '2442-2020',
                'telefono_whatsapp'   => '7442-2020',
                'email'               => 'lperez@gmail.com',
                'direccion'           => 'Barrio San Miguelito, Pje. Las Flores #5, Santa Ana',
                'departamento'        => 'Santa Ana',
                'municipio'           => 'Santa Ana',
                'distrito'            => 'San Miguelito',
                'latitud'             => '14.00200',
                'longitud'            => '-89.56100',
                'limite_credito'      => 1000.00,
                'saldo'               => 0.00,
                'ruta_cobro_id'       => $rutaNorteRuta->id,
                'activo'              => true,
                'ref_fam1_nombre'     => 'Irma Monterrosa',
                'ref_fam1_telefono'   => '7462-2233',
                'ref_fam1_parentesco' => 'Madre',
                'ref_fam2_nombre'     => 'David Pérez',
                'ref_fam2_telefono'   => '7463-3344',
                'ref_fam2_parentesco' => 'Hermano',
                'ref_con1_nombre'     => 'Celia Guardado',
                'ref_con1_telefono'   => '7563-4455',
                'ref_con1_trabajo'    => 'Empleada - Super Selectos',
                'ref_con2_nombre'     => 'Ernesto Amaya',
                'ref_con2_telefono'   => '7663-5566',
                'ref_con2_trabajo'    => 'Fontanero independiente',
                'sucursal_id'         => $norte->id,
            ],
            [
                'nombre'              => 'Brenda',
                'apellido'            => 'Morales Guevara',
                'nombre_conyuge'      => 'Héctor Guevara',
                'dui'                 => '03333333-2',
                'nit'                 => '0801-280990-009-7',
                'telefono_normal'     => '2443-3030',
                'telefono_whatsapp'   => '7443-3030',
                'email'               => 'bmorales@gmail.com',
                'direccion'           => 'Col. Las Palmas, Calle 5 Sur #8, Chalchuapa',
                'departamento'        => 'Santa Ana',
                'municipio'           => 'Chalchuapa',
                'distrito'            => 'Chalchuapa',
                'latitud'             => '13.98700',
                'longitud'            => '-89.67200',
                'limite_credito'      => 1500.00,
                'saldo'               => 230.00,
                'ruta_cobro_id'       => $rutaNorteRuta->id,
                'activo'              => true,
                'ref_fam1_nombre'     => 'Gloria Morales',
                'ref_fam1_telefono'   => '7473-3344',
                'ref_fam1_parentesco' => 'Hermana',
                'ref_fam2_nombre'     => 'Héctor Guevara',
                'ref_fam2_telefono'   => '7474-4455',
                'ref_fam2_parentesco' => 'Esposo',
                'ref_con1_nombre'     => 'Walter Nájera',
                'ref_con1_telefono'   => '7574-5566',
                'ref_con1_trabajo'    => 'Propietario - Tienda El Buen Precio',
                'ref_con2_nombre'     => 'Lorena Castro',
                'ref_con2_telefono'   => '7674-6677',
                'ref_con2_trabajo'    => 'Secretaria - Alcaldía Chalchuapa',
                'sucursal_id'         => $norte->id,
            ],
        ];

        foreach ($clientesCentral as $data) {
            Cliente::firstOrCreate(['dui' => $data['dui']], $data);
        }
        foreach ($clientesNorte as $data) {
            Cliente::firstOrCreate(['dui' => $data['dui']], $data);
        }

        // ── Resumen ───────────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('✅ TestingSeeder completado exitosamente.');
        $this->command->info('');
        $this->command->info('── Sucursales ──────────────────────────────────────────────');
        $this->command->info("  • Central (ID: {$central->id}) → /administrativo/{$central->id}");
        $this->command->info("  • Norte   (ID: {$norte->id})   → /administrativo/{$norte->id}");
        $this->command->info('');
        $this->command->info('── Usuarios ────────────────────────────────────────────────');
        $this->command->info('  admin@sidb.com     (super_admin)   → Central + Norte | pass: password');
        $this->command->info('  central@sidb.com   (administrador) → solo Central     | pass: password');
        $this->command->info('  norte@sidb.com     (administrador) → solo Norte       | pass: password');
        $this->command->info('  vendedor@sidb.com  (vendedor)      → solo Central     | pass: password');
        $this->command->info('');
        $this->command->info('── Datos creados ────────────────────────────────────────────');
        $this->command->info('  Categorías: 8 en Central + 8 en Norte');
        $this->command->info('  Productos:  24 en Central | 5 en Norte');
        $this->command->info('  Proveedores: 3 (El Roble, TechHome, Comfort)');
        $this->command->info('  Cobradores: 3 en Central + 1 en Norte | Rutas: 3 + 1');
        $this->command->info('  Clientes: 6 en Central + 3 en Norte (todos los campos llenos)');
        $this->command->info('');
    }
}
