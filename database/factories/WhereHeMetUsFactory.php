<?php

namespace Database\Factories;

use App\Models\GroupWhereMetUs;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhereHeMetUs>
 */
class WhereHeMetUsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true) . ' ' . $this->faker->unique()->numberBetween(1, 10000),
            'group_where_met_us_id' => GroupWhereMetUs::factory()
        ];
    }
}
