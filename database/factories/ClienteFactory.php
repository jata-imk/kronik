<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'primer_nombre' => fake()->firstName(),
            'segundo_nombre' => null,
            'apellido_paterno' => fake()->lastName(),
            'apellido_materno' => null,
            'fecha_nacimiento' => fake()->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'pais_nacimiento_id' => 1,
            'telefono_codigo_pais' => '+52',
            'telefono' => fake()->numerify('55########'),
            'email' => fake()->unique()->safeEmail(),
            'sexo' => fake()->randomElement(['masculino', 'femenino']),
            'ocupacion' => fake()->jobTitle(),
            'actividad_economica' => fake()->words(3, true),
            'ingresos_mensuales' => fake()->randomFloat(2, 10000, 100000),
            'egresos_mensuales' => fake()->randomFloat(2, 5000, 50000),
            'origen_recursos' => 'Ingresos por actividad profesional declarada.',
        ];
    }
}
