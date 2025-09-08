<?php

namespace Database\Factories;

use App\Models\Commission;
use App\Models\TypeOfPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'type_of_plan_id' => TypeOfPlan::factory(),
            'commission_id' => Commission::factory(),
            'total_sessions' => $this->faker->randomFloat(2, 0, 1000),
            'therapies_number' => $this->faker->randomFloat(2, 0, 1000),
            'number_installments' => $this->faker->randomFloat(2, 0, 1000),
            'duration' => $this->faker->randomFloat(2, 0, 1000),
            'code' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 0, 1000),
            'available' => $this->faker->boolean,
        ];
    }

    
}



