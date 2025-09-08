<?php

namespace Database\Factories;

use App\Models\{TypeOfItem,Commission};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       // $AcceptItems = ['consulta','radiografia','reporte','comparacion','terapia Fisica','traccion','ajuste'];

        return [
            'name' => $this->faker->word,
            'type_of_item_id' => $this->faker->randomElement(TypeOfItem::pluck('id')->toArray()),
            'commission_id' => Commission::factory(),
            'code' => $this->faker->word,
            'price' => $this->faker->randomFloat(2, 0, 1000),
        ];
    }
}
