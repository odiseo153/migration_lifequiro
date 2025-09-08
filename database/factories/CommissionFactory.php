<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Commission>
 */
class CommissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         return [
            'card_commission' => $this->faker->numberBetween(1,  6),
            'bank_commission' => $this->faker->numberBetween(1,  6),
            'other_commission' => $this->faker->numberBetween(1, 6),
    ];
    }
}
