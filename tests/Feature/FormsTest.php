<?php

namespace Tests\Feature;

use App\Enums\FormFieldType;
use App\Models\Area;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class FormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('forms.index'))->assertRedirect(route('login'));
    }

    public function test_the_index_lists_open_forms_and_separates_closed_ones(): void
    {
        $user = User::factory()->create();

        Form::factory()->create(['title' => 'Formulario abierto']);
        Form::factory()->closed()->create(['title' => 'Formulario cerrado']);
        Form::factory()->expired()->create(['title' => 'Formulario vencido']);

        $this->actingAs($user);

        $this->get(route('forms.index'))
            ->assertOk()
            ->assertSee('Formularios')
            ->assertSee('Formulario abierto')
            ->assertDontSee('Formulario cerrado')
            ->assertDontSee('Formulario vencido');

        Volt::test('pages.forms.index')
            ->set('status', 'cerrados')
            ->assertSee('Formulario cerrado')
            ->assertSee('Formulario vencido')
            ->assertDontSee('Formulario abierto');
    }

    public function test_the_index_marks_forms_the_user_already_answered(): void
    {
        $user = User::factory()->create();
        $form = Form::factory()->create();

        FormSubmission::factory()->create([
            'form_id' => $form->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('forms.index'))
            ->assertOk()
            ->assertSee('Ya respondiste');
    }

    public function test_editors_can_create_a_form_with_dynamic_fields(): void
    {
        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create();

        $this->actingAs($editor);

        Volt::test('pages.forms.create')
            ->set('title', 'Solicitud de equipo')
            ->set('description', 'Cuéntanos qué necesitas para trabajar mejor.')
            ->set('closesAt', today()->addDays(7)->toDateString())
            ->set('areaId', $area->id)
            ->set('fields', [
                ['label' => 'Nombre', 'type' => FormFieldType::Text->value, 'required' => true, 'optionsText' => ''],
                ['label' => 'Área', 'type' => FormFieldType::Select->value, 'required' => false, 'optionsText' => "Personas\nTecnología"],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $form = Form::query()->sole();

        $this->assertSame('Solicitud de equipo', $form->title);
        $this->assertTrue($form->creator->is($editor));
        $this->assertSame($area->id, $form->area_id);
        $this->assertCount(2, $form->fields);

        $select = $form->fields->firstWhere('label', 'Área');

        $this->assertSame(FormFieldType::Select, $select->type);
        $this->assertSame(['Personas', 'Tecnología'], $select->options);
        $this->assertFalse($select->is_required);
        $this->assertTrue($form->fields->firstWhere('label', 'Nombre')->is_required);
    }

    public function test_regular_users_cannot_create_forms(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('create', Form::class));

        $this->actingAs($user)
            ->get(route('forms.create'))
            ->assertForbidden();
    }

    public function test_form_creation_validates_its_limits(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor);

        Volt::test('pages.forms.create')
            ->set('title', str_repeat('a', 121))
            ->set('description', str_repeat('b', 501))
            ->set('closesAt', today()->subDay()->toDateString())
            ->set('fields', [
                ['label' => '', 'type' => FormFieldType::Text->value, 'required' => false, 'optionsText' => ''],
            ])
            ->call('save')
            ->assertHasErrors(['title', 'description', 'closesAt', 'fields.0.label']);

        Volt::test('pages.forms.create')
            ->set('title', 'Selección incompleta')
            ->set('fields', [
                ['label' => 'Elige una opción', 'type' => FormFieldType::Select->value, 'required' => false, 'optionsText' => 'Solo una'],
            ])
            ->call('save')
            ->assertHasErrors(['fields.0.optionsText']);

        $tooManyFields = array_fill(0, 11, [
            'label' => 'Campo',
            'type' => FormFieldType::Text->value,
            'required' => false,
            'optionsText' => '',
        ]);

        Volt::test('pages.forms.create')
            ->set('title', 'Demasiados campos')
            ->set('fields', $tooManyFields)
            ->call('save')
            ->assertHasErrors(['fields']);

        $this->assertDatabaseCount('forms', 0);
    }

    public function test_users_can_submit_a_form_once(): void
    {
        $creator = User::factory()->create();
        $form = Form::factory()->create(['user_id' => $creator->id]);

        $name = FormField::factory()->required()->create([
            'form_id' => $form->id,
            'label' => 'Nombre',
            'type' => FormFieldType::Text,
        ]);
        $email = FormField::factory()->required()->create([
            'form_id' => $form->id,
            'label' => 'Correo',
            'type' => FormFieldType::Email,
        ]);
        $choice = FormField::factory()->select(['A', 'B'])->required()->create([
            'form_id' => $form->id,
            'label' => 'Elige',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('pages.forms.show', ['form' => $form])
            ->set('answers', [
                $name->id => 'Ana Lucía',
                $email->id => 'ana@example.com',
                $choice->id => 'B',
            ])
            ->call('submit')
            ->assertHasNoErrors();

        $submission = FormSubmission::query()->sole();

        $this->assertTrue($submission->user->is($user));
        $this->assertSame('Ana Lucía', $submission->answerFor($name->id));
        $this->assertSame('ana@example.com', $submission->answerFor($email->id));
        $this->assertSame('B', $submission->answerFor($choice->id));

        Volt::test('pages.forms.show', ['form' => $form])
            ->set('answers', [
                $name->id => 'Otra vez',
                $email->id => 'otra@example.com',
                $choice->id => 'A',
            ])
            ->call('submit');

        $this->assertSame(1, FormSubmission::query()->count());
        $this->assertSame('Ana Lucía', $submission->fresh()->answerFor($name->id));
    }

    public function test_submissions_validate_required_fields_and_field_types(): void
    {
        $user = User::factory()->create();
        $form = Form::factory()->create();

        $name = FormField::factory()->required()->create([
            'form_id' => $form->id,
            'label' => 'Nombre',
            'type' => FormFieldType::Text,
        ]);
        $email = FormField::factory()->required()->create([
            'form_id' => $form->id,
            'label' => 'Correo',
            'type' => FormFieldType::Email,
        ]);
        $number = FormField::factory()->required()->create([
            'form_id' => $form->id,
            'label' => 'Cantidad',
            'type' => FormFieldType::Number,
        ]);
        $date = FormField::factory()->required()->create([
            'form_id' => $form->id,
            'label' => 'Fecha',
            'type' => FormFieldType::Date,
        ]);
        $choice = FormField::factory()->select(['A', 'B'])->required()->create([
            'form_id' => $form->id,
            'label' => 'Elige',
        ]);

        $this->actingAs($user);

        Volt::test('pages.forms.show', ['form' => $form])
            ->set('answers', [
                $name->id => '',
                $email->id => 'no-es-un-correo',
                $number->id => 'muchos',
                $date->id => 'ayer',
                $choice->id => 'Z',
            ])
            ->call('submit')
            ->assertHasErrors([
                'answers.'.$name->id,
                'answers.'.$email->id,
                'answers.'.$number->id,
                'answers.'.$date->id,
                'answers.'.$choice->id,
            ]);

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_closed_and_expired_forms_reject_submissions(): void
    {
        $user = User::factory()->create();
        $closed = Form::factory()->closed()->create();
        $closedField = FormField::factory()->required()->create(['form_id' => $closed->id]);

        $this->assertFalse($user->can('submit', $closed));

        $this->actingAs($user);

        Volt::test('pages.forms.show', ['form' => $closed])
            ->set('answers', [$closedField->id => 'Tarde'])
            ->call('submit');

        $this->assertDatabaseCount('form_submissions', 0);

        $expired = Form::factory()->expired()->create();
        $expiredField = FormField::factory()->required()->create(['form_id' => $expired->id]);

        $this->assertFalse($user->can('submit', $expired));

        Volt::test('pages.forms.show', ['form' => $expired])
            ->set('answers', [$expiredField->id => 'Tarde'])
            ->call('submit');

        $this->assertDatabaseCount('form_submissions', 0);
    }

    public function test_users_see_their_own_answers_after_submitting(): void
    {
        $user = User::factory()->create();
        $form = Form::factory()->create();
        $field = FormField::factory()->create([
            'form_id' => $form->id,
            'label' => 'Nombre',
        ]);

        FormSubmission::factory()->create([
            'form_id' => $form->id,
            'user_id' => $user->id,
            'answers' => [$field->id => 'Ana Lucía'],
        ]);

        $this->actingAs($user)
            ->get(route('forms.show', $form))
            ->assertOk()
            ->assertSee('Tu respuesta')
            ->assertSee('Ana Lucía')
            ->assertDontSee('Completa los campos');
    }

    public function test_only_the_creator_or_an_admin_can_see_all_responses(): void
    {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $respondent = User::factory()->create();
        $form = Form::factory()->create(['user_id' => $creator->id]);
        $field = FormField::factory()->create([
            'form_id' => $form->id,
            'label' => 'Comentario',
        ]);

        FormSubmission::factory()->create([
            'form_id' => $form->id,
            'user_id' => $respondent->id,
            'answers' => [$field->id => 'Respuesta privada'],
        ]);

        $this->assertTrue($creator->can('viewResponses', $form));
        $this->assertFalse($otherUser->can('viewResponses', $form));
        $this->assertTrue($admin->can('viewResponses', $form));

        $this->actingAs($creator)
            ->get(route('forms.show', $form))
            ->assertOk()
            ->assertSee('Respuestas recibidas')
            ->assertSee('Respuesta privada');

        $this->actingAs($otherUser)
            ->get(route('forms.show', $form))
            ->assertOk()
            ->assertDontSee('Respuestas recibidas')
            ->assertDontSee('Respuesta privada');

        $this->actingAs($admin)
            ->get(route('forms.show', $form))
            ->assertOk()
            ->assertSee('Respuestas recibidas')
            ->assertSee('Respuesta privada');
    }

    public function test_the_creator_can_close_and_reopen_a_form(): void
    {
        $creator = User::factory()->create();
        $form = Form::factory()->create([
            'user_id' => $creator->id,
            'closes_at' => today()->subDay(),
        ]);

        $this->actingAs($creator);

        Volt::test('pages.forms.show', ['form' => $form])
            ->call('close')
            ->assertHasNoErrors();

        $this->assertFalse($form->fresh()->is_active);
        $this->assertSame('Cerrado', $form->fresh()->statusLabel());

        Volt::test('pages.forms.show', ['form' => $form->fresh()])
            ->call('reopen')
            ->assertHasNoErrors();

        $form->refresh();

        $this->assertTrue($form->is_active);
        $this->assertTrue($form->isOpen());
        $this->assertTrue($form->closes_at->isAfter(today()));
    }

    public function test_only_the_creator_or_an_admin_can_manage_a_form(): void
    {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $form = Form::factory()->create(['user_id' => $creator->id]);

        $this->assertTrue($creator->can('update', $form));
        $this->assertTrue($creator->can('delete', $form));
        $this->assertFalse($otherUser->can('update', $form));
        $this->assertFalse($otherUser->can('delete', $form));
        $this->assertTrue($admin->can('update', $form));
        $this->assertTrue($admin->can('delete', $form));

        $this->actingAs($creator);

        Volt::test('pages.forms.show', ['form' => $form])
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('forms', ['id' => $form->id]);

        $adminForm = Form::factory()->create(['user_id' => $creator->id]);

        $this->actingAs($admin);

        Volt::test('pages.forms.show', ['form' => $adminForm])
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('forms', ['id' => $adminForm->id]);
    }
}
