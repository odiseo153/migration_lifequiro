<?php

namespace Database\Factories;

use App\Models\{Sector,City,Province,Patient,Branch,PatientGroup,WhereHeMetUs};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'birth_date' => $this->faker->date,
            'mobile' => $this->faker->phoneNumber,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->safeEmail,
            'identity_document_type' => $this->faker->randomElement(['Cedula', 'Pasaporte']),
            'identity_document' => $this->faker->unique()->numerify('##########'),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'occupation' => $this->faker->jobTitle,
            'civil_status' => $this->faker->randomElement(['Soltero', 'Casado', 'Viudo']),
            'address' => $this->faker->address,
            'comment' => $this->faker->sentence,
            'province_id' => Province::factory(),
            'city_id' => City::factory(),
            'sector_id' => Sector::factory(),
            'patient_group_id' => $this->faker->randomElement(PatientGroup::pluck('id')->toArray()),
            'branch_id' => Branch::factory(),
            'where_met_us_id' => WhereHeMetUs::factory(),
            'token' => $this->faker->unique()->numberBetween(1000, 9999),
            'token_expires_at' => $this->faker->dateTimeBetween('-20 minutes', 'now')
        ];
    }
}



