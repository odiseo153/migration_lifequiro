<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AssignedPlan;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Installment>
 */
class InstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date_paid' => $this->faker->date,
            'amount' => $this->faker->numberBetween(1, 10000),
            'is_it_paid' => $this->faker->boolean,
            'assigned_plan_id' => AssignedPlan::factory(),

        ];
    }
}
