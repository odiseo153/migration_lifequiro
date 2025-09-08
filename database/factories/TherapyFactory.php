<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Therapy>
 */
class TherapyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cant_therapies' => $this->faker->numberBetween(1, 100),
            'number_installments' => $this->faker->numberBetween(1, 12),
            'time_in_day' => $this->faker->numberBetween(1, 365),
        ];
    }
}
