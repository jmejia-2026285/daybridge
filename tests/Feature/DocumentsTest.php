<?php

namespace Tests\Feature;

use App\Enums\DocumentCategory;
use App\Models\Area;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('documents.index'))->assertRedirect(route('login'));
    }

    public function test_active_users_can_browse_the_document_library(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create(['name' => 'Personas']);

        Document::factory()->create([
            'title' => 'Manual de bienvenida',
            'area_id' => $area->id,
            'category' => DocumentCategory::Manual,
            'file_name' => 'manual.pdf',
            'file_size' => 2_500_000,
        ]);

        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('Documentos')
            ->assertSee('Manual de bienvenida')
            ->assertSee('Manual')
            ->assertSee('Personas')
            ->assertSee('2,4 MB');
    }

    public function test_documents_can_be_filtered_by_search_area_and_category(): void
    {
        $user = User::factory()->create();
        $people = Area::factory()->create(['name' => 'Personas', 'slug' => 'personas']);
        $finance = Area::factory()->create(['name' => 'Finanzas', 'slug' => 'finanzas']);

        Document::factory()->create([
            'title' => 'Política de vacaciones',
            'description' => 'Días libres y aprobaciones',
            'area_id' => $people->id,
            'category' => DocumentCategory::Policy,
        ]);

        Document::factory()->create([
            'title' => 'Acta de cierre',
            'description' => 'Acuerdos del trimestre',
            'area_id' => $finance->id,
            'category' => DocumentCategory::Minutes,
        ]);

        $this->actingAs($user);

        Volt::test('pages.documents.index')
            ->set('search', 'vacaciones')
            ->assertSee('Política de vacaciones')
            ->assertDontSee('Acta de cierre');

        Volt::test('pages.documents.index')
            ->set('area', 'finanzas')
            ->assertSee('Acta de cierre')
            ->assertDontSee('Política de vacaciones');

        Volt::test('pages.documents.index')
            ->set('category', DocumentCategory::Policy->value)
            ->assertSee('Política de vacaciones')
            ->assertDontSee('Acta de cierre');
    }

    public function test_editors_can_upload_a_document(): void
    {
        Storage::fake('local');

        $editor = User::factory()->editor()->create();
        $area = Area::factory()->create();

        $this->actingAs($editor);

        Volt::test('pages.documents.index')
            ->set('title', 'Manual de operaciones')
            ->set('description', 'Procesos internos del equipo.')
            ->set('areaId', $area->id)
            ->set('documentCategory', DocumentCategory::Manual->value)
            ->set('file', UploadedFile::fake()->create('manual.pdf', 500, 'application/pdf'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('title', '')
            ->assertSet('showForm', false);

        $document = Document::query()->sole();

        $this->assertSame('Manual de operaciones', $document->title);
        $this->assertSame('Procesos internos del equipo.', $document->description);
        $this->assertSame(DocumentCategory::Manual, $document->category);
        $this->assertSame($area->id, $document->area_id);
        $this->assertSame('manual.pdf', $document->file_name);
        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertTrue($document->uploader->is($editor));

        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_regular_users_cannot_upload_documents(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('create', Document::class));

        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertDontSee('Subir documento');
    }

    public function test_document_uploads_validate_their_limits(): void
    {
        Storage::fake('local');

        $editor = User::factory()->editor()->create();

        $this->actingAs($editor);

        Volt::test('pages.documents.index')
            ->set('title', str_repeat('a', 121))
            ->set('description', str_repeat('b', 501))
            ->set('areaId', 999999)
            ->set('documentCategory', 'INVALIDA')
            ->call('save')
            ->assertHasErrors([
                'title',
                'description',
                'areaId',
                'documentCategory',
                'file',
            ]);

        Volt::test('pages.documents.index')
            ->set('title', 'Archivo no permitido')
            ->set('documentCategory', DocumentCategory::Other->value)
            ->set('file', UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'))
            ->call('save')
            ->assertHasErrors(['file']);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_only_the_uploader_or_an_admin_can_delete_a_document(): void
    {
        Storage::fake('local');

        $uploader = User::factory()->editor()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $document = Document::factory()->create([
            'user_id' => $uploader->id,
            'file_path' => 'documents/borrar.pdf',
        ]);

        Storage::disk('local')->put($document->file_path, 'contenido');

        $this->assertTrue($uploader->can('delete', $document));
        $this->assertFalse($otherUser->can('delete', $document));
        $this->assertTrue($admin->can('delete', $document));

        $this->actingAs($uploader);

        Volt::test('pages.documents.index')
            ->call('delete', $document->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->file_path);
    }

    public function test_active_users_can_download_documents(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $document = Document::factory()->create([
            'file_path' => 'documents/descarga.pdf',
            'file_name' => 'descarga.pdf',
        ]);

        Storage::disk('local')->put($document->file_path, 'contenido del documento');

        $this->actingAs($user)
            ->get(route('documents.download', $document))
            ->assertOk()
            ->assertDownload('descarga.pdf');
    }

    public function test_downloads_require_authentication_and_an_existing_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $document = Document::factory()->create(['file_path' => 'documents/inexistente.pdf']);

        $this->get(route('documents.download', $document))->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('documents.download', $document))
            ->assertNotFound();
    }
}
