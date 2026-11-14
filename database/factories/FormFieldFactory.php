<?php

namespace Database\Factories;

use App\Enums\FormFieldType;
use App\Models\Form;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormField>
 */
class FormFieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'label' => fake()->sentence(4),
            'type' => FormFieldType::Text,
            'options' => null,
            'is_required' => false,
            'sort_order' => 0,
        ];
    }

    /**
     * @param  array<int, string>  $options
     */
    public function select(array $options = ['Opción A', 'Opción B']): static
    {
        return $this->state(fn (): array => [
            'type' => FormFieldType::Select,
            'options' => $options,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn (): array => [
            'is_required' => true,
        ]);
    }
}
