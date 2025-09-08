<?php

namespace Database\Factories;

use App\Models\AssignedPlan;
use App\Models\Branch;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgrammingHistory>
 */
class ProgrammingHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day' => $this->faker->dayOfMonth,
            'hour' => $this->faker->time('H:i:s'),
            'is_active' => $this->faker->boolean,
            'activation_date' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'patient_id' =>  $this->faker->randomElement(Patient::pluck('id')->toArray()),
            'assigned_plan_id' => AssignedPlan::factory(),
            'branch_id' => $this->faker->randomElement(Branch::pluck('id')->toArray())
        ];
    }
}



