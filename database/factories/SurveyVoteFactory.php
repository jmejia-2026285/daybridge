<?php

namespace Database\Factories;

use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyVote>
 */
class SurveyVoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'survey_question_id' => SurveyQuestion::factory(),
            'survey_option_id' => SurveyOption::factory(),
            'user_id' => User::factory(),
        ];
    }
}
