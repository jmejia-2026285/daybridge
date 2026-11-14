<?php

namespace Tests\Feature;

use App\Livewire\Chat\Index as ChatIndex;
use App\Livewire\ProfileAvatar;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** PNG 1x1 válido. */
    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    public function test_avatar_upload_rejects_php_payload_disguised_as_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $payload = 'data:image/php;base64,'.base64_encode('<?php system($_GET["c"]); ?>');

        $this->actingAs($user)
            ->postJson(route('profile.avatar.update'), ['image_base64' => $payload])
            ->assertStatus(422);

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertNull($user->fresh()->avatar);
    }

    public function test_avatar_extension_is_derived_from_content_not_from_client(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // El cliente dice "html" pero el contenido es un PNG real.
        $this->actingAs($user)
            ->postJson(route('profile.avatar.update'), ['image_base64' => 'data:image/html;base64,'.self::PNG_1PX])
            ->assertOk()
            ->assertJson(['success' => true]);

        $files = Storage::disk('public')->allFiles();
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.png', $files[0]);
    }

    public function test_replacing_avatar_deletes_previous_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $dataUri = 'data:image/png;base64,'.self::PNG_1PX;

        // La respuesta usa una ruta relativa: funciona por localhost o por la IP de la red.
        $first = $this->actingAs($user)->postJson(route('profile.avatar.update'), ['image_base64' => $dataUri])->assertOk();
        $first->assertJsonPath('path', fn (string $path): bool => str_starts_with($path, 'storage/avatars/'));

        $this->actingAs($user)->postJson(route('profile.avatar.update'), ['image_base64' => $dataUri])->assertOk();

        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_avatar_component_cannot_target_another_user(): void
    {
        Storage::fake('public');
        $attacker = User::factory()->create();
        $victim = User::factory()->create(['avatar' => 'storage/avatars/victim.png']);

        // Ya no existe una propiedad userId manipulable desde el cliente.
        $this->assertFalse(property_exists(ProfileAvatar::class, 'userId'));

        Livewire::actingAs($attacker)
            ->test(ProfileAvatar::class)
            ->call('removeAvatar');

        $this->assertSame('storage/avatars/victim.png', $victim->fresh()->avatar);
    }

    public function test_chat_search_does_not_leak_conversations_of_other_users(): void
    {
        $intruder = User::factory()->create();
        [$alice, $bob] = User::factory()->count(2)->create();

        $private = Conversation::startDirect($alice, $bob);
        $private->update(['subject' => 'Nómina confidencial']);

        $component = Livewire::actingAs($intruder)
            ->test(ChatIndex::class)
            ->set('search', 'Nómina');

        $this->assertSame(0, $component->instance()->conversations()->total());
    }

    public function test_login_does_not_reveal_inactive_accounts_without_valid_password(): void
    {
        User::factory()->inactive()->create(['email' => 'inactiva@example.com']);

        Volt::test('pages.auth.login')
            ->set('form.email', 'inactiva@example.com')
            ->set('form.password', 'contraseña-incorrecta')
            ->call('login')
            ->assertHasErrors(['form.email' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_inactive_user_with_correct_password_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['email' => 'inactiva@example.com']);

        Volt::test('pages.auth.login')
            ->set('form.email', 'inactiva@example.com')
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors('form.email');

        $this->assertGuest();
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_last_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        Volt::actingAs($admin)
            ->test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertHasErrors('password');

        $this->assertNotNull($admin->fresh());
    }
}
