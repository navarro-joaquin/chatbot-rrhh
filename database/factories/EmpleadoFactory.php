<?php

namespace Database\Factories;

use App\Models\Empleado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Empleado>
 */
class EmpleadoFactory extends Factory
{
    protected $model = Empleado::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_completo' => $this->faker->name(),
            'carnet_identidad' => $this->faker->unique()->numerify('#######'),
            'telefono' => $this->faker->unique()->numerify('########'),
            'correo_electronico' => $this->faker->unique()->safeEmail(),
            'estado' => true,
        ];
    }
}
