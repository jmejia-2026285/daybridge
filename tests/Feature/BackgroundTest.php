<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackgroundTest extends TestCase
{
    use RefreshDatabase;

    /** PNG 1x1 válido. */
    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private const PNG_DATA_URI = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_admin_can_change_the_app_background(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.background.update'), ['image_base64' => self::PNG_DATA_URI])
            ->assertOk()
            ->assertJson(['success' => true]);

        $path = $response->json('path');
        $this->assertStringStartsWith('storage/background/', $path);
        $this->assertStringEndsWith('.png', $path);

        $this->assertSame($path, Setting::get(Setting::BACKGROUND_KEY));
        Storage::disk('public')->assertExists(str($path)->after('storage/')->toString());
    }

    public function test_only_administrators_can_change_the_background(): void
    {
        Storage::fake('public');
        $collaborator = User::factory()->create();

        $this->actingAs($collaborator)
            ->postJson(route('admin.background.update'), ['image_base64' => self::PNG_DATA_URI])
            ->assertForbidden();

        $this->assertNull(Setting::get(Setting::BACKGROUND_KEY));
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_rejects_payloads_that_are_not_real_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)
            ->postJson(route('admin.background.update'), ['image_base64' => 'data:image/php;base64,'.base64_encode('<?php echo 1;')])
            ->assertStatus(422);

        $this->assertNull(Setting::get(Setting::BACKGROUND_KEY));
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_replacing_background_deletes_previous_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)->postJson(route('admin.background.update'), ['image_base64' => self::PNG_DATA_URI])->assertOk();
        $this->actingAs($admin)->postJson(route('admin.background.update'), ['image_base64' => self::PNG_DATA_URI])->assertOk();

        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_removing_background_restores_the_default(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)->postJson(route('admin.background.update'), ['image_base64' => self::PNG_DATA_URI])->assertOk();

        $this->actingAs($admin)
            ->postJson(route('admin.background.destroy'))
            ->assertOk()
            ->assertJson(['success' => true, 'path' => null]);

        $this->assertNull(Setting::get(Setting::BACKGROUND_KEY));
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_layout_uses_the_custom_background_when_set(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)->postJson(route('admin.background.update'), ['image_base64' => self::PNG_DATA_URI])->assertOk();

        $path = Setting::get(Setting::BACKGROUND_KEY);
        $this->assertNotNull($path);

        $this->actingAs($admin)
            ->get(route('admin.appearance'))
            ->assertOk()
            ->assertSee(asset($path));
    }

    public function test_layout_falls_back_to_mountains_without_custom_background(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)
            ->get(route('admin.appearance'))
            ->assertOk()
            ->assertSee('fondo-montanas-desktop');
    }

    public function test_non_administrator_cannot_open_the_appearance_page(): void
    {
        $collaborator = User::factory()->create();

        $this->actingAs($collaborator)
            ->get(route('admin.appearance'))
            ->assertForbidden();
    }
}
