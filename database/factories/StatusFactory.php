<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Status>
 */
class StatusFactory extends Factory
{
    protected $model = Status::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'state' => $this->faker->unique()->randomElement([
                'Completada',
                'Programada',
                'Pospuesta',
                'No Asistió',
                'Atendiendo',
                'En Espera',
                'Radiografía',
                'Reprogramada',
                'No Radiografia',
                'No comparación',
                'Confirmada',
                'Desactivada'
            ]),
            'order' => $this->faker->unique()->numberBetween(1, 100),
        ];
    }
}