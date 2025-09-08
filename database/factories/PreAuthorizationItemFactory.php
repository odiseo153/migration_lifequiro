<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{
    PreAuthorization,
    Item
};

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PreAuthorizationItem>
 */
class PreAuthorizationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pre_authorization_id' => PreAuthorization::factory(),
            'item_id' => $this->faker->randomElement(Item::pluck('id')->toArray()),
            'no_document' => $this->faker->randomNumber(8),
            'is_paid' => $this->faker->boolean(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'mount_authorize' => $this->faker->numberBetween(100, 1000),
            'cost' => $this->faker->numberBetween(50, 500),
        ];
    }
}
