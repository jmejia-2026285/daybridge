<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SurveysTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('surveys.index'))->assertRedirect(route('login'));
    }

    public function test_the_index_lists_open_surveys_and_separates_closed_ones(): void
    {
        $user = User::factory()->create();

        Survey::factory()->create(['title' => 'Encuesta abierta']);
        Survey::factory()->closed()->create(['title' => 'Encuesta cerrada']);
        Survey::factory()->expired()->create(['title' => 'Encuesta vencida']);

        $this->actingAs($user);

        $this->get(route('surveys.index'))
            ->assertOk()
            ->assertSee('Encuestas')
            ->assertSee('Encuesta abierta')
            ->assertDontSee('Encuesta cerrada')
            ->assertDontSee('Encuesta vencida');

        Volt::test('pages.surveys.index')
            ->set('status', 'cerradas')
            ->assertSee('Encuesta cerrada')
            ->assertSee('Encuesta vencida')
            ->assertDontSee('Encuesta abierta');
    }

    public function test_the_index_marks_surveys_the_user_already_answered(): void
    {
        $user = User::factory()->create();
        $survey = Survey::factory()->create();
        $question = SurveyQuestion::factory()->create(['survey_id' => $survey->id]);
        $option = SurveyOption::factory()->create(['survey_question_id' => $question->id]);

        SurveyVote::factory()->create([
            'survey_question_id' => $question->id,
            'survey_option_id' => $option->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('surveys.index'))
            ->assertOk()
            ->assertSee('Ya votaste');
    }

    public function test_admins_and_editors_can_create_a_survey_with_questions_and_options(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor);

        Volt::test('pages.surveys.create')
            ->set('title', 'Clima del trimestre')
            ->set('description', 'Tres preguntas rápidas para el equipo.')
            ->set('closesAt', today()->addDays(10)->toDateString())
            ->set('questions', [
                [
                    'prompt' => '¿Cómo te sientes con la carga actual?',
                    'options' => ['Muy bien', 'Bien', 'Podría mejorar'],
                ],
                [
                    'prompt' => '¿Qué necesitas del equipo?',
                    'options' => ['Más contexto', 'Mejores herramientas'],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $survey = Survey::query()->sole();

        $this->assertSame('Clima del trimestre', $survey->title);
        $this->assertTrue($survey->creator->is($editor));
        $this->assertCount(2, $survey->questions);
        $this->assertCount(3, $survey->questions->first()->options);
        $this->assertSame('Muy bien', $survey->questions->first()->options->first()->label);
    }

    public function test_regular_users_cannot_create_surveys(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('surveys.create'))
            ->assertForbidden();
    }

    public function test_survey_creation_validates_the_limits(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor);

        Volt::test('pages.surveys.create')
            ->set('title', str_repeat('a', 121))
            ->set('description', str_repeat('b', 501))
            ->set('closesAt', today()->subDay()->toDateString())
            ->set('questions', [
                [
                    'prompt' => str_repeat('c', 256),
                    'options' => ['', ''],
                ],
            ])
            ->call('save')
            ->assertHasErrors([
                'title',
                'description',
                'closesAt',
                'questions.0.prompt',
                'questions.0.options.0',
                'questions.0.options.1',
            ]);

        Volt::test('pages.surveys.create')
            ->set('title', 'Encuesta inválida')
            ->set('questions', [
                [
                    'prompt' => 'Pregunta con demasiadas opciones',
                    'options' => ['Uno', 'Dos', 'Tres', 'Cuatro', 'Cinco', 'Seis'],
                ],
            ])
            ->call('save')
            ->assertHasErrors(['questions.0.options']);

        $this->assertDatabaseCount('surveys', 0);
    }

    public function test_users_can_vote_once_per_question(): void
    {
        $creator = User::factory()->create();
        $voter = User::factory()->create();
        $survey = Survey::factory()->create(['user_id' => $creator->id]);
        $question = SurveyQuestion::factory()->create(['survey_id' => $survey->id]);
        $firstOption = SurveyOption::factory()->create(['survey_question_id' => $question->id]);
        $secondOption = SurveyOption::factory()->create(['survey_question_id' => $question->id]);

        $this->actingAs($voter);

        Volt::test('pages.surveys.show', ['survey' => $survey])
            ->set('answers', [$question->id => $firstOption->id])
            ->call('vote', $question->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('survey_votes', [
            'survey_question_id' => $question->id,
            'survey_option_id' => $firstOption->id,
            'user_id' => $voter->id,
        ]);

        Volt::test('pages.surveys.show', ['survey' => $survey])
            ->set('answers', [$question->id => $secondOption->id])
            ->call('vote', $question->id)
            ->assertHasErrors(['answers.'.$question->id]);

        $this->assertSame(1, SurveyVote::query()->count());
        $this->assertDatabaseHas('survey_votes', [
            'survey_question_id' => $question->id,
            'survey_option_id' => $firstOption->id,
            'user_id' => $voter->id,
        ]);
    }

    public function test_votes_require_an_existing_option_from_the_same_question(): void
    {
        $voter = User::factory()->create();
        $survey = Survey::factory()->create();
        $question = SurveyQuestion::factory()->create(['survey_id' => $survey->id]);
        $otherQuestion = SurveyQuestion::factory()->create(['survey_id' => $survey->id]);
        $foreignOption = SurveyOption::factory()->create(['survey_question_id' => $otherQuestion->id]);

        $this->actingAs($voter);

        Volt::test('pages.surveys.show', ['survey' => $survey])
            ->set('answers', [$question->id => $foreignOption->id])
            ->call('vote', $question->id)
            ->assertHasErrors(['answers.'.$question->id]);

        Volt::test('pages.surveys.show', ['survey' => $survey])
            ->set('answers', [$question->id => 999999])
            ->call('vote', $question->id)
            ->assertHasErrors(['answers.'.$question->id]);

        $this->assertDatabaseCount('survey_votes', 0);
    }

    public function test_closed_surveys_reject_new_votes(): void
    {
        $voter = User::factory()->create();
        $survey = Survey::factory()->closed()->create();
        $question = SurveyQuestion::factory()->create(['survey_id' => $survey->id]);
        $option = SurveyOption::factory()->create(['survey_question_id' => $question->id]);

        $this->assertFalse($survey->isOpen());
        $this->assertFalse($voter->can('vote', $survey));

        $this->actingAs($voter);

        Volt::test('pages.surveys.show', ['survey' => $survey])
            ->set('answers', [$question->id => $option->id])
            ->call('vote', $question->id);

        $this->assertDatabaseCount('survey_votes', 0);
    }

    public function test_results_are_hidden_until_the_user_votes(): void
    {
        $voter = User::factory()->create();
        $survey = Survey::factory()->create();
        $question = SurveyQuestion::factory()->create(['survey_id' => $survey->id]);
        SurveyOption::factory()->create(['survey_question_id' => $question->id, 'label' => 'Opción única']);

        $this->actingAs($voter)
            ->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSee('Enviar respuesta')
            ->assertDontSee('Tu respuesta');

        Volt::test('pages.surveys.show', ['survey' => $survey])
            ->set('answers', [$question->id => $question->options()->first()->id])
            ->call('vote', $question->id)
            ->assertHasNoErrors();

        $this->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSee('Tu respuesta')
            ->assertSee('100%')
            ->assertDontSee('Enviar respuesta');
    }

    public function test_managers_and_closed_surveys_show_results_without_voting(): void
    {
        $creator = User::factory()->create();
        $survey = Survey::factory()->create(['user_id' => $creator->id]);
        $question = SurveyQuestion::factory()->create(['survey_id' => $survey->id]);
        SurveyOption::factory()->create(['survey_question_id' => $question->id]);

        $this->actingAs($creator)
            ->get(route('surveys.show', $survey))
            ->assertOk()
            ->assertSee('Todavía no hay respuestas para esta pregunta.')
            ->assertSee('Enviar respuesta');

        $survey->update(['is_active' => false]);

        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('surveys.show', $survey->fresh()))
            ->assertOk()
            ->assertSee('Todavía no hay respuestas para esta pregunta.')
            ->assertDontSee('Enviar respuesta');
    }

    public function test_the_creator_can_close_and_reopen_a_survey(): void
    {
        $creator = User::factory()->create();
        $survey = Survey::factory()->create(['user_id' => $creator->id, 'closes_at' => today()->subDay()]);

        $this->actingAs($creator);

        Volt::test('pages.surveys.show', ['survey' => $survey])
            ->call('close')
            ->assertHasNoErrors();

        $this->assertFalse($survey->fresh()->is_active);
        $this->assertSame('Cerrada', $survey->fresh()->statusLabel());

        Volt::test('pages.surveys.show', ['survey' => $survey->fresh()])
            ->call('reopen')
            ->assertHasNoErrors();

        $survey->refresh();

        $this->assertTrue($survey->is_active);
        $this->assertTrue($survey->isOpen());
        $this->assertTrue($survey->closes_at->isAfter(today()));
    }

    public function test_only_the_creator_or_an_admin_can_manage_a_survey(): void
    {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $survey = Survey::factory()->create(['user_id' => $creator->id]);

        $this->assertTrue($creator->can('update', $survey));
        $this->assertTrue($creator->can('delete', $survey));
        $this->assertFalse($otherUser->can('update', $survey));
        $this->assertFalse($otherUser->can('delete', $survey));
        $this->assertTrue($admin->can('update', $survey));
        $this->assertTrue($admin->can('delete', $survey));

        $this->actingAs($creator);

        Volt::test('pages.surveys.show', ['survey' => $survey])
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
    }
}
