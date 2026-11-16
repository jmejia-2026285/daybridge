<?php

namespace Tests\Feature;

use App\Enums\InventoryItemStatus;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('it-desk.inventory.index'))->assertRedirect(route('login'));
    }

    public function test_regular_users_cannot_open_the_inventory(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('it-desk.inventory.index'))
            ->assertForbidden();
    }

    public function test_regular_users_cannot_download_reports(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('it-desk.inventory.export.pdf'))->assertForbidden();
        $this->actingAs($user)->get(route('it-desk.inventory.export.word'))->assertForbidden();
    }

    public function test_it_desk_members_see_the_inventory_with_its_items(): void
    {
        $technician = User::factory()->technician()->create();
        $item = InventoryItem::factory()->create(['name' => 'Teclado mecánico']);

        $this->actingAs($technician)
            ->get(route('it-desk.inventory.index'))
            ->assertOk()
            ->assertSee('Inventario')
            ->assertSee('Teclado mecánico');
    }

    public function test_it_desk_members_can_register_quantity_items(): void
    {
        $technician = User::factory()->technician()->create();
        $category = InventoryCategory::factory()->create(['name' => 'Cables y adaptadores']);

        $this->actingAs($technician);

        Volt::test('pages.it-desk.inventory.index')
            ->set('itemCategoryId', (string) $category->id)
            ->set('itemName', 'Cable HDMI 2.0')
            ->set('itemQuantity', '8')
            ->set('itemStatus', InventoryItemStatus::InStock->value)
            ->call('saveItem')
            ->assertSee('registrado');

        $item = InventoryItem::query()->where('name', 'Cable HDMI 2.0')->firstOrFail();

        $this->assertSame(8, $item->quantity);
        $this->assertFalse($item->is_unique);
        $this->assertSame($technician->id, $item->created_by);
    }

    public function test_unique_items_always_count_one_unit(): void
    {
        $technician = User::factory()->technician()->create();
        $category = InventoryCategory::factory()->create(['name' => 'Laptops dañadas']);

        $this->actingAs($technician);

        Volt::test('pages.it-desk.inventory.index')
            ->set('itemCategoryId', (string) $category->id)
            ->set('itemName', 'Laptop Lenovo ThinkPad')
            ->set('itemIsUnique', true)
            ->set('itemSerial', 'SN-9911')
            // Aunque el formulario oculte la cantidad, una unidad con serie es siempre 1.
            ->set('itemQuantity', '5')
            ->set('itemStatus', InventoryItemStatus::Damaged->value)
            ->call('saveItem');

        $item = InventoryItem::query()->where('name', 'Laptop Lenovo ThinkPad')->firstOrFail();

        $this->assertTrue($item->is_unique);
        $this->assertSame(1, $item->quantity);
        $this->assertSame('SN-9911', $item->serial);
    }

    public function test_technicians_can_edit_items_but_cannot_delete_them(): void
    {
        $technician = User::factory()->technician()->create();
        $item = InventoryItem::factory()->create([
            'name' => 'Monitor Dell 24',
            'status' => InventoryItemStatus::InStock,
        ]);

        $this->actingAs($technician);

        Volt::test('pages.it-desk.inventory.index')
            ->call('editItem', $item->id)
            ->set('itemName', 'Monitor Dell 27')
            ->set('itemStatus', InventoryItemStatus::Warehouse->value)
            ->call('saveItem')
            ->assertSee('actualizado');

        $this->assertSame('Monitor Dell 27', $item->fresh()->name);
        $this->assertSame(InventoryItemStatus::Warehouse, $item->fresh()->status);

        $component = Volt::test('pages.it-desk.inventory.index');

        try {
            $component->call('deleteItem', $item->id);
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertModelExists($item->fresh() ?? $item);
    }

    public function test_admins_can_delete_items(): void
    {
        $admin = User::factory()->admin()->create();
        $item = InventoryItem::factory()->create(['name' => 'Impresora vieja']);

        $this->actingAs($admin);

        Volt::test('pages.it-desk.inventory.index')
            ->call('deleteItem', $item->id)
            ->assertSee('eliminado');

        $this->assertModelMissing($item);
    }

    public function test_category_management_is_restricted_to_it_desk_admins(): void
    {
        $technician = User::factory()->technician()->create();
        $itAdmin = User::factory()->technician()->itDeskAdmin()->create();

        $this->actingAs($technician);

        $component = Volt::test('pages.it-desk.inventory.index');

        try {
            $component->call('saveCategory');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertDatabaseCount('inventory_categories', 0);

        $this->actingAs($itAdmin);

        Volt::test('pages.it-desk.inventory.index')
            ->set('categoryName', 'Consumibles')
            ->set('categoryDescription', 'Tóner, cables y humidores.')
            ->call('saveCategory')
            ->assertSee('creada');

        $this->assertDatabaseHas('inventory_categories', ['name' => 'Consumibles']);
    }

    public function test_items_can_be_filtered_by_search_and_status(): void
    {
        $technician = User::factory()->technician()->create();
        $inStock = InventoryItem::factory()->create([
            'name' => 'Mouse inalámbrico',
            'status' => InventoryItemStatus::InStock,
        ]);
        $damaged = InventoryItem::factory()->create([
            'name' => 'Teclado inalámbrico',
            'status' => InventoryItemStatus::Damaged,
        ]);

        $this->actingAs($technician);

        Volt::test('pages.it-desk.inventory.index')
            ->set('search', 'inalámbrico')
            ->assertSee('Mouse inalámbrico')
            ->assertSee('Teclado inalámbrico');

        Volt::test('pages.it-desk.inventory.index')
            ->set('search', 'Mouse')
            ->assertSee('Mouse inalámbrico')
            ->assertDontSee('Teclado inalámbrico');

        Volt::test('pages.it-desk.inventory.index')
            ->set('statusFilter', InventoryItemStatus::Damaged->value)
            ->assertSee('Teclado inalámbrico')
            ->assertDontSee('Mouse inalámbrico');

        $this->assertSame(InventoryItemStatus::Damaged, $damaged->status);
        $this->assertSame(InventoryItemStatus::InStock, $inStock->status);
    }

    public function test_the_pdf_report_can_be_downloaded_and_filtered(): void
    {
        $technician = User::factory()->technician()->create();
        $category = InventoryCategory::factory()->create(['name' => 'Herramientas']);
        $item = InventoryItem::factory()->create([
            'inventory_category_id' => $category->id,
            'name' => 'Destornillador precision',
            'status' => InventoryItemStatus::Warehouse,
        ]);

        $this->actingAs($technician)
            ->get(route('it-desk.inventory.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($technician)
            ->get(route('it-desk.inventory.export.pdf', [
                'categoria' => $category->id,
                'estado' => InventoryItemStatus::Warehouse->value,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_the_word_report_can_be_downloaded(): void
    {
        $technician = User::factory()->technician()->create();
        InventoryItem::factory()->create(['name' => 'Multimetro digital']);

        $this->actingAs($technician)
            ->get(route('it-desk.inventory.export.word'))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            );
    }

    public function test_items_reject_an_invalid_status(): void
    {
        $technician = User::factory()->technician()->create();
        $category = InventoryCategory::factory()->create();

        $this->actingAs($technician);

        Volt::test('pages.it-desk.inventory.index')
            ->set('itemCategoryId', (string) $category->id)
            ->set('itemName', 'Artículo raro')
            ->set('itemStatus', 'ESTADO_INVALIDO')
            ->call('saveItem')
            ->assertHasErrors(['itemStatus']);

        $this->assertDatabaseMissing('inventory_items', ['name' => 'Artículo raro']);
    }
}
