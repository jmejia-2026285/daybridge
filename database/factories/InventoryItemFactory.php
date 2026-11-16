<?php

namespace Database\Factories;

use App\Enums\InventoryItemStatus;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_category_id' => InventoryCategory::factory(),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'quantity' => $this->faker->numberBetween(1, 20),
            'is_unique' => false,
            'serial' => null,
            'status' => $this->faker->randomElement(InventoryItemStatus::cases()),
            'location' => $this->faker->randomElement(['Caja fuerte', 'Estante A', 'Bodega central', 'Taller']),
            'notes' => null,
        ];
    }

    public function uniqueUnit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_unique' => true,
            'quantity' => 1,
            'serial' => $this->faker->unique()->ean8(),
        ]);
    }
}
