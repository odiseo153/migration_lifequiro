<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AssignedPlan>
 */
class AssignedPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = Plan::factory()->create();

        return [
            'plan_id'=> $plan->id,
            'patient_id'=> $this->faker->randomElement(Patient::pluck('id')->toArray()),
            'date_start' => $this->faker->date,
            'date_end' => $this->faker->date,
            'plan_name' => $plan->name,
            'paid_type' => $this->faker->randomElement([1,2]),
        ];
    }
}
