<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiagnosisAndTreatment>
 */
class DiagnosisAndTreatmentFactory extends Factory
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
            'problem_diagnosis' => $this->faker->sentence(10), // Diagnóstico del problema
            'long_term_treatment' => $this->faker->sentence(15), // Tratamiento a largo plazo
            'short_term_treatment' => $this->faker->sentence(15), // Tratamiento a corto plazo
            'session_frequency' => $this->faker->randomElement(['1 vez por semana', '2 veces por semana', '3 veces por semana']), // Frecuencia de sesiones
            'treatment_modalities' => $this->faker->randomElement(['terapia_manual', 'electroterapia', 'ejercicio', 'terapeutico', 'otro']), // Modalidades de tratamiento
            'treatment_modalities_other' => $this->faker->optional()->sentence(10), // Especificar otra modalidad (opcional)
        ];
    }
}