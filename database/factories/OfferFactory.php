<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Crear 3 items y obtener sus IDs y precios
        $items = Item::factory()->count(3)->create()->map(function ($item) {
            return [
                'id' => $item->id,
                'price' => $item->price,
            ];
        })->toArray();

        $total = 0;
            foreach ($items as $itemData) {
              $total += $itemData['price'] * ($itemData['price'] ?? 1);
            }

        return [
            'name' => $this->faker->word,
            'expired_date' => $this->faker->dateTimeBetween('now', '+2 months'),
            'total' => $total,
        ];
    }
}