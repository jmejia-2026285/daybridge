<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationAndPwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_layout_exposes_the_module_sheet_and_manifest(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('manifest.webmanifest')
            ->assertSee('Todos los módulos')
            ->assertSee('Cumpleaños')
            ->assertSee('Mensajes')
            ->assertSee('Notificaciones');
    }

    public function test_admin_sees_the_administration_module_in_the_sheet(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee(route('admin.people'));
    }

    public function test_regular_users_do_not_see_the_administration_module(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertDontSee(route('admin.people'));
    }

    public function test_manifest_contains_installable_pwa_metadata(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertIsArray($manifest);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('#0B1416', $manifest['theme_color']);
        $this->assertSame('#020C14', $manifest['background_color']);

        $sizes = collect($manifest['icons'])->pluck('sizes')->all();

        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);

        $this->assertTrue(collect($manifest['icons'])->contains(
            fn (array $icon): bool => in_array('maskable', (array) ($icon['purpose'] ?? []), true)
                || str_contains((string) ($icon['purpose'] ?? ''), 'maskable'),
        ));

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_offline_page_is_available(): void
    {
        $this->get(route('offline'))
            ->assertOk()
            ->assertSee('Modo sin conexión')
            ->assertSee('Reintentar');
    }
}
