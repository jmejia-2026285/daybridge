<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CopySqliteToMysqlTest extends TestCase
{
    use RefreshDatabase;

    private string $sourcePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourcePath = tempnam(sys_get_temp_dir(), 'daybridge-legacy-');

        config(['database.connections.legacy_test' => [
            'driver' => 'sqlite',
            'database' => $this->sourcePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        $this->artisan('migrate', ['--database' => 'legacy_test', '--force' => true])->assertSuccessful();
    }

    protected function tearDown(): void
    {
        DB::purge('legacy_test');
        File::delete($this->sourcePath);

        parent::tearDown();
    }

    public function test_it_copies_every_row_keeping_ids_and_relations(): void
    {
        $legacy = DB::connection('legacy_test');

        $legacy->table('areas')->insert(['id' => 5, 'name' => 'Diseño', 'slug' => 'diseno', 'color' => '#FF5A1F', 'sort_order' => 1]);
        $legacy->table('users')->insert([
            'id' => 12, 'name' => 'María Castillo', 'email' => 'maria@daybridge.test', 'password' => 'hash',
            'role' => 'admin', 'is_active' => 1, 'area_id' => 5, 'birth_date' => '1990-03-14',
        ]);
        $legacy->table('posts')->insert([
            'id' => 30, 'area_id' => 5, 'user_id' => 12, 'title' => 'Bienvenida', 'excerpt' => 'Hola',
            'body' => 'Cuerpo con acentos: ñandú', 'type' => 'news', 'published_at' => '2026-11-05 14:54:46',
        ]);

        $this->artisan('db:copy-sqlite-to-mysql', ['--source' => 'legacy_test', '--target' => 'sqlite'])
            ->expectsOutputToContain('Copia completa')
            ->assertSuccessful();

        $post = Post::query()->with(['author', 'area'])->findOrFail(30);

        $this->assertSame('Cuerpo con acentos: ñandú', $post->body);
        $this->assertSame('maria@daybridge.test', User::query()->findOrFail(12)->email);
        $this->assertSame('Diseño', Area::query()->findOrFail(5)->name);
        $this->assertSame('1990-03-14', User::query()->findOrFail(12)->birth_date->toDateString());
        $this->assertSame(
            DB::connection('legacy_test')->table('migrations')->count(),
            DB::table('migrations')->count(),
            'La tabla migrations del destino no se debe duplicar.',
        );
    }

    public function test_it_refuses_to_mix_data_unless_truncate_is_given(): void
    {
        Area::factory()->create(['slug' => 'existente']);
        DB::connection('legacy_test')->table('areas')->insert(['id' => 1, 'name' => 'Legado', 'slug' => 'legado', 'color' => '#000000']);

        $this->artisan('db:copy-sqlite-to-mysql', ['--source' => 'legacy_test', '--target' => 'sqlite'])
            ->expectsOutputToContain('El destino ya tiene datos en: areas')
            ->assertFailed();

        $this->assertDatabaseHas('areas', ['slug' => 'existente']);

        $this->artisan('db:copy-sqlite-to-mysql', ['--source' => 'legacy_test', '--target' => 'sqlite', '--truncate' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('areas', ['slug' => 'existente']);
        $this->assertDatabaseHas('areas', ['slug' => 'legado']);
    }

    public function test_it_stops_when_the_target_is_missing_tables(): void
    {
        DB::connection('legacy_test')->getSchemaBuilder()->create('legacy_only', function (Blueprint $table): void {
            $table->id();
        });

        $this->artisan('db:copy-sqlite-to-mysql', ['--source' => 'legacy_test', '--target' => 'sqlite'])
            ->expectsOutputToContain('Faltan tablas en el destino: legacy_only')
            ->assertFailed();
    }

    public function test_source_must_be_sqlite_and_different_from_target(): void
    {
        $this->artisan('db:copy-sqlite-to-mysql', ['--source' => 'sqlite', '--target' => 'sqlite'])
            ->expectsOutputToContain('no pueden ser la misma conexión')
            ->assertFailed();
    }
}
