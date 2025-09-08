<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TypeOfItem>
 */
class TypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $AcceptItems = ['consulta','radiografia','reporte','comparacion','terapia Fisica','traccion','ajuste'];

        return [
            'name' => $this->faker->randomElement($AcceptItems),
            ];

    }
}
