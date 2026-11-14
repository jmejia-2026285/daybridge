<?php

namespace Tests\Feature;

use App\Enums\ItDeskRole;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_is_signed_out_by_the_active_middleware(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@daybridge.test',
        ]);

        Volt::test('pages.auth.login')
            ->set('form.email', 'inactive@daybridge.test')
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors('form.email');

        $this->assertGuest();
    }

    public function test_only_admins_can_open_people_administration(): void
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)
            ->get(route('admin.people'))
            ->assertForbidden();
    }

    public function test_admin_can_open_people_administration(): void
    {
        $admin = User::factory()->admin()->create();
        $person = User::factory()->create(['name' => 'Persona Listada']);

        $this->actingAs($admin)
            ->get(route('admin.people'))
            ->assertOk()
            ->assertSee('Persona Listada')
            ->assertSee('Roles de plataforma');
    }

    public function test_admin_can_change_a_team_members_role(): void
    {
        $admin = User::factory()->admin()->create();
        $person = User::factory()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.people')
            ->call('updateRole', $person->id, UserRole::Editor->value)
            ->assertHasNoErrors();

        $this->assertSame(UserRole::Editor, $person->fresh()->role);
    }

    public function test_admin_cannot_demote_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.people')
            ->call('updateRole', $admin->id, UserRole::User->value)
            ->assertSee('No puedes cambiar tu propio rol');

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_admin_can_assign_it_desk_access(): void
    {
        $admin = User::factory()->admin()->create();
        $person = User::factory()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.people')
            ->call('updateItDeskRole', $person->id, ItDeskRole::Tecnico->value)
            ->assertHasNoErrors();

        $this->assertSame(ItDeskRole::Tecnico, $person->fresh()->it_desk_role);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.people')
            ->call('toggleActive', $admin->id)
            ->assertSee('No puedes desactivar tu propia cuenta');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_deactivate_and_reactivate_an_account(): void
    {
        $admin = User::factory()->admin()->create();
        $person = User::factory()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.people')->call('toggleActive', $person->id);
        $this->assertFalse($person->fresh()->is_active);

        Volt::test('pages.admin.people')->call('toggleActive', $person->id);
        $this->assertTrue($person->fresh()->is_active);
    }

    public function test_admin_can_demote_another_admin_when_more_than_one_remains(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.people')
            ->call('updateRole', $other->id, UserRole::User->value)
            ->assertHasNoErrors();

        $this->assertSame(UserRole::User, $other->fresh()->role);
    }

    public function test_admin_can_create_team_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.people')
            ->set('createName', 'Nueva Persona')
            ->set('createEmail', 'nueva@daybridge.test')
            ->set('createPhone', '+502 5510 1010')
            ->set('createJobTitle', 'Analista')
            ->set('createAreaId', (string) $area->id)
            ->set('createRole', UserRole::Editor->value)
            ->set('createPassword', 'Segura2026Acceso')
            ->set('createPasswordConfirmation', 'Segura2026Acceso')
            ->call('createUser')
            ->assertHasNoErrors();

        $created = User::query()->where('email', 'nueva@daybridge.test')->firstOrFail();

        $this->assertSame('Nueva Persona', $created->name);
        $this->assertSame(UserRole::Editor, $created->role);
        $this->assertSame($area->id, $created->area_id);
        $this->assertSame('+502 5510 1010', $created->phone);
        $this->assertNotSame('Segura2026Acceso', $created->password);
        $this->assertTrue(Hash::check('Segura2026Acceso', $created->password));
    }

    public function test_account_creation_requires_a_strong_password_and_unique_email(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.people')
            ->set('createName', 'Cuenta Débil')
            ->set('createEmail', 'cuenta.debil@daybridge.test')
            ->set('createRole', UserRole::User->value)
            ->set('createPassword', '123')
            ->set('createPasswordConfirmation', '123')
            ->call('createUser')
            ->assertHasErrors(['createPassword']);

        Volt::test('pages.admin.people')
            ->set('createName', 'Cuenta Repetida')
            ->set('createEmail', $existing->email)
            ->set('createRole', UserRole::User->value)
            ->set('createPassword', 'Segura2026Acceso')
            ->set('createPasswordConfirmation', 'Segura2026Acceso')
            ->call('createUser')
            ->assertHasErrors(['createEmail']);

        $this->assertFalse(User::query()->where('name', 'Cuenta Débil')->exists());
        $this->assertFalse(User::query()->where('name', 'Cuenta Repetida')->exists());
    }

    public function test_only_admins_can_create_accounts(): void
    {
        $editor = User::factory()->editor()->create();

        $this->assertFalse($editor->can('create', User::class));

        $this->actingAs($editor)
            ->get(route('admin.people'))
            ->assertForbidden();

        $this->assertFalse(User::query()->where('email', 'sin.permiso@daybridge.test')->exists());
    }

    public function test_admin_can_delete_a_person_account(): void
    {
        $admin = User::factory()->admin()->create();
        $person = User::factory()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.people')
            ->call('confirmDelete', $person->id)
            ->call('deleteUser')
            ->assertSee('fue eliminada de Daybridge');

        $this->assertModelMissing($person);
    }

    public function test_admins_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);
        $this->assertFalse($admin->can('delete', $admin));

        $component = Volt::test('pages.admin.people');

        try {
            $component->call('confirmDelete', $admin->id);
        } catch (AuthorizationException|\Symfony\Component\HttpKernel\Exception\HttpException) {
        }

        $this->assertModelExists($admin);
    }
}
