<?php

namespace Database\Factories;

use App\Models\{Status, Patient, TypeOfAppointments,Branch};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'note' => $this->faker->word,
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'hour' => $this->faker->dateTimeBetween('00:00', '23:59')->format('H:i'), // Formato 24 horas
            'patient_id' => $this->faker->randomElement( Patient::pluck('id')->toArray()),
            'branch_id' => $this->faker->randomElement(Branch::pluck('id')->toArray()),
            'type_of_appointment_id' => $this->faker->randomElement(TypeOfAppointments::pluck('id')->toArray()),
            'status_id' => $this->faker->randomElement(Status::pluck('id')->toArray()),

        ];
    }
}




