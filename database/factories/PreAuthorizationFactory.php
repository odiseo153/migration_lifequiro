<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Patient;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PreAuthorization>
 */


class PreAuthorizationFactory extends Factory
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
            'pre_authorization_number'=>$this->faker->randomDigit(),
            'authorization_file' => $this->faker->imageUrl(640, 480, 'business', true),
        ];
    }
}
