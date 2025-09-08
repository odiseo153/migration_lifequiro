<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PhysicalExamination>
 */
class PhysicalExaminationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => $this->faker->randomElement(Patient::pluck('id')->toArray()),
            'weight_status' => $this->faker->randomElement(['Normal', 'Bajo', 'Alto']),
            'weight_specify' => $this->faker->optional()->sentence(),
            'height_status' => $this->faker->randomElement(['Normal', 'Bajo', 'Alto']),
            'height_specify' => $this->faker->optional()->sentence(),
            'size_status' => $this->faker->randomElement(['Normal', 'Pequeño', 'Grande']),
            'size_specify' => $this->faker->optional()->sentence(),
            'physical_activity' => $this->faker->boolean(),
            'injuries_description' => $this->faker->optional()->sentence(),
            'posture_status' => $this->faker->randomElement(['Normal', 'Leve desviación', 'Severa desviación']),
            'posture_specify' => $this->faker->optional()->sentence(),
            'body_symmetry' => $this->faker->randomElement(['Normal', 'Asimétrico']),
            'asymmetry_details' => $this->faker->optional()->sentence(),
            'has_pain' => $this->faker->boolean(),
            'pain_scale' => $this->faker->optional()->numberBetween(1, 10),
            'pain_location' => $this->faker->optional()->sentence(),
            'pain_intensity' => $this->faker->optional()->randomElement(['Agudo', 'Cronico', 'Punzante']),
            'pain_factors' => $this->faker->optional()->sentence(),
            'has_cramps' => $this->faker->boolean(),
            'cramps_details' => $this->faker->optional()->sentence(),
            'has_tingling' => $this->faker->boolean(),
            'tingling_details' => $this->faker->optional()->sentence(),
            'hip' => $this->faker->randomElement(['Normal', 'Limitado']),
            'knee' => $this->faker->randomElement(['Normal', 'Limitado']),
            'ankle' => $this->faker->randomElement(['Normal', 'Limitado']),
            'shoulder' => $this->faker->randomElement(['Normal', 'Limitado']),
            'elbow' => $this->faker->randomElement(['Normal', 'Limitado']),
            'wrist' => $this->faker->randomElement(['Normal', 'Limitado']),
            'muscle_strength' => $this->faker->optional()->numberBetween(0, 5),
            'upper_limbs_strength' => $this->faker->optional()->numberBetween(0, 5),
            'lower_limbs_strength' => $this->faker->optional()->numberBetween(0, 5),
            'trunk_strength' => $this->faker->optional()->numberBetween(0, 5),
        ];
    }
}