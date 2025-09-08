<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HistoryMedical>
 */
class HistoryMedicalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tests = ['radiografias', 'resonancias', 'ecografias', 'otras', null];
        
        return [
            'patient_id' => $this->faker->randomElement(\App\Models\Patient::pluck('id')->toArray()),
            'consultation_reason' => $this->faker->paragraph,
            'personal_background' => $this->faker->paragraph,
            'family_background' => $this->faker->paragraph,
            'symptoms' => $this->faker->paragraph,
            'previous_diagnoses' => $this->faker->paragraph,
            'current_medication' => $this->faker->paragraph,
            'diagnostic_tests' => $this->faker->randomElement($tests),
            'other_test' => function (array $attributes) {
                return $attributes['diagnostic_tests'] === 'otras' 
                    ? $this->faker->sentence 
                    : null;
            },
            'analytics' => $this->faker->paragraph,
        ];
    }
}
