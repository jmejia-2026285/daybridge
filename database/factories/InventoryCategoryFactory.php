<?php

namespace Database\Factories;

use App\Models\InventoryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryCategory>
 */
class InventoryCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Productos en caja',
                'Laptops dañadas',
                'Herramientas de mantenimiento',
                'Cables y adaptadores',
                'Consumibles',
            ]),
            'description' => $this->faker->sentence(),
        ];
    }
}
