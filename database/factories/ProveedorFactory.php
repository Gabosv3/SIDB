<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Proveedor>
 */
class ProveedorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'codigo' => 'PROV-' . $this->faker->unique()->numerify('###'),
            'contacto_principal' => $this->faker->name(),
            'email' => $this->faker->unique()->companyEmail(),
            'telefono' => $this->faker->phoneNumber(),
            'telefono_adicional' => $this->faker->optional()->phoneNumber(),
            'direccion' => $this->faker->address(),
            'ciudad' => $this->faker->city(),
            'departamento' => $this->faker->state(),
            'pais' => $this->faker->country(),
            'codigo_postal' => $this->faker->postcode(),
            'rfc_o_nit' => $this->faker->unique()->numerify('###########'),
            'condiciones_pago' => $this->faker->randomElement(['contado', 'credito', 'mixto']),
            'dias_credito' => $this->faker->numberBetween(0, 60),
            'descuento_comercial' => $this->faker->randomFloat(2, 0, 15),
            'activo' => true,
            'notas' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Proveedor inactivo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
