<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://moodle.daybridge.test/webservice/rest/server.php';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.moodle.enabled' => true,
            'services.moodle.url' => 'https://moodle.daybridge.test',
            'services.moodle.token' => 'secret-token',
            'modules.learning.enabled' => true,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('learning.index'))
            ->assertRedirect(route('login'));
    }

    public function test_page_explains_when_moodle_is_not_configured(): void
    {
        config(['services.moodle.enabled' => false]);
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->get(route('learning.index'))
            ->assertOk()
            ->assertSee('La plataforma de capacitación todavía no está conectada.');

        Http::assertNothingSent();
    }

    public function test_module_appears_in_navigation_only_when_enabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertSee(route('learning.index'));

        config(['modules.learning.enabled' => false]);

        $this->actingAs($user)->get('/')->assertDontSee(route('learning.index'));
    }

    public function test_users_see_their_courses_progress_and_grades(): void
    {
        $user = User::factory()->create(['email' => 'ana@daybridge.test']);

        $this->fakeMoodle([
            'core_user_get_users_by_field' => [['id' => 42, 'email' => 'ana@daybridge.test']],
            'core_enrol_get_users_courses' => [
                ['id' => 7, 'shortname' => 'SEG-101', 'fullname' => 'Seguridad de la información', 'summary' => '<p>Buenas prácticas</p>', 'progress' => 62.5, 'completed' => false, 'lastaccess' => now()->subDay()->timestamp, 'visible' => 1],
                ['id' => 8, 'shortname' => 'BIEN-01', 'fullname' => 'Bienvenida Daybridge', 'summary' => '', 'progress' => 100, 'completed' => true, 'lastaccess' => now()->subWeek()->timestamp, 'visible' => 1],
                ['id' => 9, 'shortname' => 'OCULTO', 'fullname' => 'Curso oculto', 'visible' => 0],
            ],
            'gradereport_overview_get_course_grades' => ['grades' => [['courseid' => 8, 'grade' => '95.00']]],
        ]);

        $this->actingAs($user)
            ->get(route('learning.index'))
            ->assertOk()
            ->assertSeeInOrder(['Seguridad de la información', 'Bienvenida Daybridge'])
            ->assertSee('63%')
            ->assertSee('95.00')
            ->assertSee(route('learning.sso', ['to' => 'https://moodle.daybridge.test/course/view.php?id=7']), false)
            ->assertSee('Buenas prácticas')
            ->assertDontSee('<p>Buenas prácticas</p>', false)
            ->assertDontSee('Curso oculto');

        $this->assertSame(42, $user->fresh()->moodle_user_id);

        Http::assertSent(fn (Request $request): bool => $request['wstoken'] === 'secret-token'
            && $request['wsfunction'] === 'core_user_get_users_by_field'
            && $request['values'] === ['ana@daybridge.test']);
    }

    public function test_courses_are_cached_and_refresh_reloads_them(): void
    {
        $user = User::factory()->create(['moodle_user_id' => 42]);

        $this->fakeMoodle([
            'core_enrol_get_users_courses' => [['id' => 7, 'fullname' => 'Seguridad de la información', 'progress' => 10]],
            'gradereport_overview_get_course_grades' => ['grades' => []],
        ]);

        $this->actingAs($user);

        $component = Volt::test('pages.learning.index')->assertSee('Seguridad de la información');
        Volt::test('pages.learning.index')->assertSee('Seguridad de la información');

        Http::assertSentCount(2);

        $component->call('refresh')->assertSee('Cursos actualizados desde Moodle.');

        Http::assertSentCount(4);
    }

    public function test_users_can_filter_courses_by_status(): void
    {
        $user = User::factory()->create(['moodle_user_id' => 42]);

        $this->fakeMoodle([
            'core_enrol_get_users_courses' => [
                ['id' => 7, 'fullname' => 'Curso en marcha', 'progress' => 40, 'lastaccess' => now()->timestamp],
                ['id' => 8, 'fullname' => 'Curso terminado', 'progress' => 100, 'completed' => true],
            ],
            'gradereport_overview_get_course_grades' => ['grades' => []],
        ]);

        $this->actingAs($user);

        Volt::test('pages.learning.index')
            ->call('setStatus', 'completed')
            ->assertSee('Curso terminado')
            ->assertDontSee('Curso en marcha');
    }

    public function test_page_explains_when_the_user_has_no_moodle_account(): void
    {
        $user = User::factory()->create(['email' => 'nuevo@daybridge.test']);

        $this->fakeMoodle(['core_user_get_users_by_field' => []]);

        $this->actingAs($user)
            ->get(route('learning.index'))
            ->assertOk()
            ->assertSee('No encontramos tu cuenta de Moodle.')
            ->assertSee('nuevo@daybridge.test');

        $this->assertNull($user->fresh()->moodle_user_id);
    }

    public function test_moodle_errors_show_a_friendly_message_without_leaking_details(): void
    {
        $user = User::factory()->create(['moodle_user_id' => 42]);

        $this->fakeMoodle([
            'core_enrol_get_users_courses' => ['exception' => 'moodle_exception', 'errorcode' => 'invalidtoken', 'message' => 'Token inválido secret-token'],
        ]);

        $this->actingAs($user)
            ->get(route('learning.index'))
            ->assertOk()
            ->assertSee('Daybridge no tiene permiso para consultar Moodle.')
            ->assertDontSee('secret-token');
    }

    public function test_connection_failures_are_handled(): void
    {
        $user = User::factory()->create(['moodle_user_id' => 42]);

        Http::fake([self::ENDPOINT => Http::response('Bad gateway', 502)]);

        $this->actingAs($user)
            ->get(route('learning.index'))
            ->assertOk()
            ->assertSee('La plataforma de capacitación no responde en este momento.');
    }

    public function test_missing_grade_permissions_do_not_hide_the_courses(): void
    {
        $user = User::factory()->create(['moodle_user_id' => 42]);

        $this->fakeMoodle([
            'core_enrol_get_users_courses' => [['id' => 7, 'fullname' => 'Seguridad de la información', 'progress' => 10]],
            'gradereport_overview_get_course_grades' => ['exception' => 'required_capability_exception', 'errorcode' => 'nopermissions', 'message' => 'Sin permiso'],
        ]);

        $this->actingAs($user)
            ->get(route('learning.index'))
            ->assertOk()
            ->assertSee('Seguridad de la información')
            ->assertDontSee('Calificación');
    }

    public function test_sync_command_links_existing_accounts_and_creates_missing_ones(): void
    {
        $linked = User::factory()->create(['email' => 'ana@daybridge.test']);
        $missing = User::factory()->create(['email' => 'Luis.Perez@daybridge.test', 'name' => 'Luis Pérez Gómez']);
        User::factory()->inactive()->create(['email' => 'baja@daybridge.test']);

        Http::fake([self::ENDPOINT => function (Request $request) {
            return match ($request['wsfunction']) {
                'core_user_get_users_by_field' => Http::response($request['values'] === ['ana@daybridge.test'] ? [['id' => 42]] : []),
                'core_user_create_users' => Http::response([['id' => 77, 'username' => 'luis.perez@daybridge.test']]),
            };
        }]);

        $this->artisan('moodle:sync-users', ['--create' => true])->assertSuccessful();

        $this->assertSame(42, $linked->fresh()->moodle_user_id);
        $this->assertSame(77, $missing->fresh()->moodle_user_id);

        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'core_user_create_users'
            && $request['users'][0]['username'] === 'luis.perez@daybridge.test'
            && $request['users'][0]['firstname'] === 'Luis'
            && $request['users'][0]['lastname'] === 'Pérez Gómez'
            && (int) $request['users'][0]['createpassword'] === 1);

        Http::assertNotSent(fn (Request $request): bool => in_array('baja@daybridge.test', (array) ($request['values'] ?? []), true));
    }

    public function test_sync_command_without_create_only_links(): void
    {
        $user = User::factory()->create(['email' => 'nuevo@daybridge.test']);

        $this->fakeMoodle(['core_user_get_users_by_field' => []]);

        $this->artisan('moodle:sync-users')->assertSuccessful();

        $this->assertNull($user->fresh()->moodle_user_id);
        Http::assertNotSent(fn (Request $request): bool => $request['wsfunction'] === 'core_user_create_users');
    }

    public function test_sync_command_fails_when_moodle_is_not_configured(): void
    {
        config(['services.moodle.token' => null]);

        $this->artisan('moodle:sync-users')->assertFailed();
    }

    public function test_sso_redirects_to_a_one_time_moodle_login_url(): void
    {
        $user = User::factory()->create(['email' => 'ana@daybridge.test', 'moodle_user_id' => 42]);

        $this->fakeMoodle([
            'auth_userkey_request_login_url' => ['loginurl' => 'https://moodle.daybridge.test/auth/userkey/login.php?key=abc123'],
        ]);

        $this->actingAs($user)
            ->get(route('learning.sso'))
            ->assertRedirect('https://moodle.daybridge.test/auth/userkey/login.php?key=abc123');

        Http::assertSent(fn (Request $request): bool => $request['wsfunction'] === 'auth_userkey_request_login_url'
            && $request['user']['email'] === 'ana@daybridge.test'
            && filled($request['user']['ip']));
    }

    public function test_sso_can_target_a_specific_moodle_page(): void
    {
        $user = User::factory()->create(['moodle_user_id' => 42]);
        $courseUrl = 'https://moodle.daybridge.test/course/view.php?id=7';

        $this->fakeMoodle([
            'auth_userkey_request_login_url' => ['loginurl' => 'https://moodle.daybridge.test/auth/userkey/login.php?key=abc123'],
        ]);

        $this->actingAs($user)
            ->get(route('learning.sso', ['to' => $courseUrl]))
            ->assertRedirect('https://moodle.daybridge.test/auth/userkey/login.php?key=abc123&wantsurl='.urlencode($courseUrl));
    }

    public function test_sso_ignores_targets_outside_moodle(): void
    {
        $user = User::factory()->create(['moodle_user_id' => 42]);

        $this->fakeMoodle([
            'auth_userkey_request_login_url' => ['loginurl' => 'https://moodle.daybridge.test/auth/userkey/login.php?key=abc123'],
        ]);

        $this->actingAs($user)
            ->get(route('learning.sso', ['to' => 'https://malicioso.test/robo']))
            ->assertRedirect('https://moodle.daybridge.test/auth/userkey/login.php?key=abc123');
    }

    public function test_sso_redirects_back_when_the_user_has_no_moodle_account(): void
    {
        $user = User::factory()->create(['email' => 'nuevo@daybridge.test']);

        $this->fakeMoodle(['core_user_get_users_by_field' => []]);

        $this->actingAs($user)
            ->get(route('learning.sso'))
            ->assertRedirect(route('learning.index'));

        Http::assertNotSent(fn (Request $request): bool => $request['wsfunction'] === 'auth_userkey_request_login_url');
    }

    public function test_sso_redirects_back_when_moodle_is_not_configured(): void
    {
        config(['services.moodle.enabled' => false]);

        Http::fake();

        $this->actingAs(User::factory()->create())
            ->get(route('learning.sso'))
            ->assertRedirect(route('learning.index'));

        Http::assertNothingSent();
    }

    public function test_sso_redirects_back_when_moodle_rejects_the_request(): void
    {
        $user = User::factory()->create(['moodle_user_id' => 42]);

        $this->fakeMoodle([
            'auth_userkey_request_login_url' => ['exception' => 'moodle_exception', 'errorcode' => 'nopermissions', 'message' => 'Sin permiso'],
        ]);

        $this->actingAs($user)
            ->get(route('learning.sso'))
            ->assertRedirect(route('learning.index'));
    }

    /**
     * @param  array<string, array<int|string, mixed>>  $responses
     */
    private function fakeMoodle(array $responses): void
    {
        Http::fake([self::ENDPOINT => fn (Request $request) => Http::response($responses[$request['wsfunction']] ?? [])]);
    }
}
