<?php

namespace Database\Factories;

use App\Models\Compra;
use App\Models\Proveedor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compra>
 */
class CompraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 10000);
        $descuento = $this->faker->randomFloat(2, 0, $subtotal * 0.2);
        $impuestoPorcentaje = $this->faker->randomElement([0, 8, 16, 19]);
        $subtotalConDescuento = $subtotal - $descuento;
        $impuestoMonto = ($subtotalConDescuento * $impuestoPorcentaje) / 100;
        $total = $subtotalConDescuento + $impuestoMonto;

        return [
            'numero_compra' => Compra::generarNumeroPedido(),
            'proveedor_id' => Proveedor::factory(),
            'fecha_compra' => $this->faker->dateTimeBetween('-3 months'),
            'fecha_entrega_estimada' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'fecha_entrega_real' => null,
            'usuario_id' => User::factory(),
            'subtotal' => $subtotal,
            'descuento_monto' => $descuento,
            'impuesto_porcentaje' => $impuestoPorcentaje,
            'impuesto_monto' => $impuestoMonto,
            'total' => $total,
            'saldo_pendiente' => $total,
            'forma_pago' => $this->faker->randomElement(['efectivo', 'transferencia', 'cheque', 'tarjeta', 'credito']),
            'condicion_pago' => $this->faker->randomElement(['contado', 'credito', 'mixta']),
            'dias_credito' => $this->faker->numberBetween(0, 60),
            'fecha_vencimiento' => null,
            'estado' => $this->faker->randomElement(['pendiente', 'recibida', 'completada']),
        ];
    }

    /**
     * Compra completada
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'estado' => 'completada',
                'saldo_pendiente' => 0,
                'fecha_entrega_real' => Carbon::now(),
            ];
        });
    }

    /**
     * Compra pendiente
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pendiente',
        ]);
    }
}
