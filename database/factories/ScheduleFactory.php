<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        
        // Generar horas en intervalos de 30 minutos entre 7:00 y 20:00
        $baseTime = Carbon::createFromTime(7, 0, 0);
        $randomInterval = $this->faker->numberBetween(0, 26);
        $startTime = $baseTime->addMinutes(30 * $randomInterval);
        
        return [
            'branch_id' => Branch::factory(),
            'day' => $this->faker->randomElement([
                'Monday', 'Tuesday', 'Wednesday', 
                'Thursday', 'Friday', 'Saturday', 'Sunday'
            ]),
            'hour' => $startTime->format('H:i:s'),
            'available' => $this->faker->boolean(50) // 80% probabilidad de estar disponible
        ];

    }
}
