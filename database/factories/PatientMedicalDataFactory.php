<?php

namespace Database\Factories;

use App\Models\{PatientMedicalData,Patient};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientMedicalData>
 */
class PatientMedicalDataFactory extends Factory
{
    protected $model = PatientMedicalData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'x_rays' => $this->faker->numberBetween(0, 10),
            're_evaluation' => $this->faker->date(),
            'frecuency' => $this->faker->word(),
            'answer' => $this->faker->sentence(),
            'duration' => $this->faker->word(),
            'start_plan' => $this->faker->word(),
            'observations' => $this->faker->paragraph(),
        ];
    }
}