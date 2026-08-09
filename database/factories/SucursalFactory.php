<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SucursalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'clave' => fake()->unique()->bothify('SUC-####'),
            'prefijo_folio' => fake()->unique()->lexify('???'),
            'activa' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['activa' => false]);
    }
}
