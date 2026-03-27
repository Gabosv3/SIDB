<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 15 proveedores de ejemplo
        Proveedor::factory(15)->create();

        // Crear algunos proveedores específicos
        $proveedores = [
            [
                'nombre' => 'Distribuidora General S.A.',
                'codigo' => 'PROV-DG001',
                'contacto_principal' => 'Carlos Mendoza',
                'email' => 'contacto@distribuidorageneral.com',
                'telefono' => '+502 2345 6789',
                'direccion' => 'Calle Principal 123',
                'ciudad' => 'Guatemala',
                'departamento' => 'Guatemala',
                'pais' => 'Guatemala',
                'condiciones_pago' => 'credito',
                'dias_credito' => 30,
                'descuento_comercial' => 5.00,
                'activo' => true,
            ],
            [
                'nombre' => 'Comercial Importadora S.A.',
                'codigo' => 'PROV-CI002',
                'contacto_principal' => 'María García López',
                'email' => 'ventas@comercialimportadora.com',
                'telefono' => '+502 7654 3210',
                'direccion' => 'Zona 10, Avenida Central',
                'ciudad' => 'Ciudad de Guatemala',
                'departamento' => 'Guatemala',
                'pais' => 'Guatemala',
                'condiciones_pago' => 'mixto',
                'dias_credito' => 45,
                'descuento_comercial' => 8.50,
                'activo' => true,
            ],
            [
                'nombre' => 'Suministros Logísticos Internacional',
                'codigo' => 'PROV-SLI003',
                'contacto_principal' => 'Fernando Rodríguez',
                'email' => 'info@suministroslogisticos.com',
                'telefono' => '+502 9876 5432',
                'direccion' => 'Carretera a El Salvador km 15',
                'ciudad' => 'Santa Tecla',
                'departamento' => 'La Libertad',
                'pais' => 'El Salvador',
                'condiciones_pago' => 'contado',
                'dias_credito' => 0,
                'descuento_comercial' => 3.00,
                'activo' => true,
            ],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::create($proveedor);
        }

        // Asociar algunos proveedores con productos existentes
        $productos = Producto::all();
        if ($productos->count() > 0) {
            $todosProv = Proveedor::all();
            foreach ($productos as $producto) {
                // Asignar 1-3 proveedores a cada producto
                $proveedoresAleatorios = $todosProv->random(rand(1, 3));
                foreach ($proveedoresAleatorios as $prov) {
                    $producto->proveedores()->attach($prov->id, [
                        'codigo_proveedor' => 'COD-' . $prov->id . '-' . $producto->id,
                        'precio_unitario' => $producto->precio_compra * $this->faker->randomFloat(1, 0.8, 1.2),
                        'cantidad_minima' => rand(5, 50),
                        'tiempo_entrega_dias' => rand(1, 14),
                    ]);
                }
            }
        }
    }

    private function faker()
    {
        return \Faker\Factory::create();
    }
}
