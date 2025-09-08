<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NeurologicalAndFunctionalEvaluation>
 */
class NeurologicalAndFunctionalEvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => $this->faker->randomElement(\App\Models\Patient::pluck('id')->toArray()),
            
            'reflexes' => $this->faker->randomElement(['normales', 'alterados', null]),
            'reflexes_details' => function (array $attributes) {
                return $attributes['reflexes'] === 'alterados' 
                    ? $this->faker->paragraph 
                    : null;
            },
            
            'sensitivity' => $this->faker->randomElement(['normales', 'alterados', null]),
            'sensitivity_details' => function (array $attributes) {
                return $attributes['sensitivity'] === 'alterados' 
                    ? $this->faker->paragraph 
                    : null;
            },
            
            'gait' => $this->faker->randomElement(['normal', 'alterada', null]),
            'gait_details' => function (array $attributes) {
                return $attributes['gait'] === 'alterada' 
                    ? $this->faker->paragraph 
                    : null;
            },
            
            'daily_activities' => $this->faker->randomElement(['normal', 'dificultad', null]),
            'daily_activities_details' => function (array $attributes) {
                return $attributes['daily_activities'] === 'dificultad' 
                    ? $this->faker->paragraph 
                    : null;
            },
            
            'technical_aids' => $this->faker->randomElement([
                'ninguna', 
                'baston', 
                'muletas', 
                'silla_de_ruedas', 
                'otros',
                null
            ]),
            'technical_aids_details' => function (array $attributes) {
                return $attributes['technical_aids'] === 'otros' 
                    ? $this->faker->paragraph 
                    : null;
            },
        ];
    }
}
