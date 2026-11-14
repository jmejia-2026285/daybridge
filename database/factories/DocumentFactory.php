<?php

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'area_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(10),
            'category' => fake()->randomElement(DocumentCategory::cases()),
            'file_path' => 'documents/'.Str::uuid().'.pdf',
            'file_name' => fake()->slug().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(50_000, 4_000_000),
        ];
    }
}
