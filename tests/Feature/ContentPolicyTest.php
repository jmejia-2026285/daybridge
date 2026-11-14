<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_and_editors_can_create_content(): void
    {
        $this->assertTrue(User::factory()->admin()->create()->can('create', Post::class));
        $this->assertTrue(User::factory()->editor()->create()->can('create', Post::class));
        $this->assertFalse(User::factory()->create()->can('create', Post::class));
    }

    public function test_editors_can_update_posts_in_their_area(): void
    {
        $area = Area::factory()->create();
        $editor = User::factory()->editor()->create(['area_id' => $area->id]);
        $otherEditor = User::factory()->editor()->create();

        $ownPost = Post::factory()->create(['user_id' => $editor->id, 'area_id' => $area->id]);
        $areaPost = Post::factory()->create(['user_id' => $otherEditor->id, 'area_id' => $area->id]);

        $this->assertTrue($editor->can('update', $ownPost));
        $this->assertTrue($editor->can('update', $areaPost));
        $this->assertFalse($editor->can('update', Post::factory()->create()));
    }

    public function test_only_admins_manage_areas(): void
    {
        $area = Area::factory()->create();

        $this->assertTrue(User::factory()->admin()->create()->can('update', $area));
        $this->assertFalse(User::factory()->editor()->create()->can('update', $area));
        $this->assertFalse(User::factory()->create()->can('delete', $area));
    }
}
