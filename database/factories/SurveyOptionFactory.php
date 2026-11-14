<?php

namespace Database\Factories;

use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyOption>
 */
class SurveyOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'survey_question_id' => SurveyQuestion::factory(),
            'label' => fake()->words(3, true),
            'sort_order' => 0,
        ];
    }
}
