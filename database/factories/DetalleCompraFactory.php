<?php

namespace Database\Factories;

use App\Models\Compra;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DetalleCompra>
 */
class DetalleCompraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 50);
        $precioUnitario = $this->faker->randomFloat(2, 10, 1000);
        $descuentoUnitario = $this->faker->randomFloat(2, 0, $precioUnitario * 0.3);
        $subtotal = ($precioUnitario - $descuentoUnitario) * $cantidad;

        return [
            'compra_id' => Compra::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'descuento_unitario' => $descuentoUnitario,
            'subtotal' => $subtotal,
            'numero_lote' => $this->faker->optional()->numerify('LOTE-########'),
            'fecha_vencimiento' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
        ];
    }
}
