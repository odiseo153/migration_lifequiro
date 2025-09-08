<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Relationship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RelatedInCenter>
 */
class RelatedInCenterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'patient_relationship_id' => Patient::factory(),
            'relationship_id' => Relationship::factory()
        ];
    }
}
