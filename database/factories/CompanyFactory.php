<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'logo_horizontal' => $this->faker->imageUrl(640, 480, 'business', true),
            'rnc' => $this->faker->randomNumber(9, true),
            'isotipo' => $this->faker->imageUrl(640, 480, 'business', true),
            'status' => $this->faker->randomElement([true,false])
        ];
    }
}



