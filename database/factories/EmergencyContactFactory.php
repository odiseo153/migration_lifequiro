<?php

namespace Database\Factories;

use App\Models\Relationship;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmergencyContact>
 */
class EmergencyContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->word,
            'relationship_id' => Relationship::factory(),
            'patient_id' => $this->faker->randomElement(Patient::pluck('id')->toArray()),
            'phone' => $this->faker->phoneNumber,
            'mobile' => $this->faker->phoneNumber,
        ];
    }
}
