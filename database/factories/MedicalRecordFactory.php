<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    protected $model = MedicalRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => $this->faker->randomElement(\App\Models\Patient::pluck('id')->toArray()),
            'has_chronic_disease' => $this->faker->boolean,
            'chronic_disease_details' => $this->faker->boolean ? $this->faker->sentence : null,
            'has_allergies' => $this->faker->boolean,
            'allergy_type' => $this->faker->boolean ? $this->faker->word : null,
            'hospitalized_last_year' => $this->faker->boolean,
            'hospitalization_reason' => $this->faker->boolean ? $this->faker->sentence : null,
            'has_disability' => $this->faker->boolean,
            'disability_type' => $this->faker->boolean ? $this->faker->word : null,
            'consultation_reason' => $this->faker->paragraph,
            'symptoms_impact_on_life' => $this->faker->paragraph,
            'medical_history' => $this->faker->paragraph,
            'current_medication' => $this->faker->paragraph,
            'pain_areas' => json_encode([
                [
                'type' => $this->faker->randomElement(['alto', 'bajo']), 
                'x' => $this->faker->randomFloat(2, 0, 100), 
                'y' => $this->faker->randomFloat(2, 0, 100)
                ], 
                [
                    'type' => $this->faker->randomElement(['alto', 'bajo']),
                 'x' => $this->faker->randomFloat(2, 0, 100),
                 'y' => $this->faker->randomFloat(2, 0, 100)
                 ]
            ]),
        ];
    }
}




