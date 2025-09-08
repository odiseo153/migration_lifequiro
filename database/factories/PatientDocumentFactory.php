<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientDocument>
 */
class PatientDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => \App\Models\Patient::factory(),
            'patient_photo' => $this->faker->imageUrl(640, 480, 'people', true, 'patient'),
            'security_carnet_photo' => $this->faker->imageUrl(640, 480, 'cards', true, 'carnet'),
            'document_front_photo' => $this->faker->imageUrl(640, 480, 'documents', true, 'front'),
            'document_back_photo' => $this->faker->imageUrl(640, 480, 'documents', true, 'back'),
            'patient_signature_photo' => $this->faker->imageUrl(640, 480, 'signatures', true, 'signature'),
        ];
    }
}
