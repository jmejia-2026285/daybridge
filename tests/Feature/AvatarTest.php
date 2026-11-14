<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    public function test_avatars_render_with_absolute_urls(): void
    {
        $user = (new User)->forceFill([
            'name' => 'Angel',
            'avatar' => 'storage/avatars/15-test.jpeg',
        ]);

        $html = view('components.avatar', ['user' => $user, 'size' => 'sm'])->render();

        $this->assertStringContainsString('src="http', $html);
        $this->assertStringContainsString('storage/avatars/15-test.jpeg', $html);
        $this->assertStringNotContainsString('src="storage', $html);
    }

    public function test_users_without_avatar_fall_back_to_area_initials(): void
    {
        $user = (new User)->forceFill([
            'name' => 'Ana Lucía Morales',
            'avatar' => null,
        ]);

        $html = view('components.avatar', ['user' => $user, 'size' => 'md'])->render();

        $this->assertStringContainsString('avatar-initials', $html);
        $this->assertMatchesRegularExpression('/>\s+AL\s+</', $html);
    }
}
