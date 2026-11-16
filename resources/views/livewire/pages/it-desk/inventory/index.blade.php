<?php

use App\Enums\InventoryItemStatus;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Inventario · IT Desk')] class extends Component
{
    public ?int $selectedCategoryId = null;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $editingItemId = null;

    public ?int $editingCategoryId = null;

    public string $itemCategoryId = '';

    public string $itemName = '';

    public string $itemDescription = '';

    public bool $itemIsUnique = false;

    public string $itemSerial = '';

    public string $itemQuantity = '1';

    public string $itemStatus = '';

    public string $itemLocation = '';

    public string $itemNotes = '';

    public string $categoryName = '';

    public string $categoryDescription = '';

    public string $statusMessage = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', InventoryItem::class), 403);

        $this->itemStatus = InventoryItemStatus::InStock->value;
    }

    #[Computed]
    public function canManageCategories(): bool
    {
        return auth()->user()->isAdmin() || auth()->user()->isItDeskAdmin();
    }

    /**
     * @return Collection<int, InventoryCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return InventoryCategory::query()
            ->withCount('items')
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, InventoryItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return InventoryItem::query()
            ->with(['category', 'createdBy'])
            ->when($this->selectedCategoryId !== null, fn ($query) => $query->where('inventory_category_id', $this->selectedCategoryId))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->search($this->search)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function totalQuantity(): int
    {
        return $this->items->sum(fn (InventoryItem $item): int => $item->effectiveQuantity());
    }

    #[Computed]
    public function statuses(): array
    {
        return array_map(
            fn (InventoryItemStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            InventoryItemStatus::cases()
        );
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
        unset($this->items, $this->totalQuantity);
        $this->statusMessage = '';
    }

    public function resetItemForm(): void
    {
        $this->editingItemId = null;
        $this->itemCategoryId = (string) ($this->selectedCategoryId ?? $this->categories->first()?->id ?? '');
        $this->itemName = '';
        $this->itemDescription = '';
        $this->itemIsUnique = false;
        $this->itemSerial = '';
        $this->itemQuantity = '1';
        $this->itemStatus = InventoryItemStatus::InStock->value;
        $this->itemLocation = '';
        $this->itemNotes = '';
        $this->resetValidation();
        $this->statusMessage = '';
    }

    public function editItem(int $itemId): void
    {
        $item = InventoryItem::query()->findOrFail($itemId);

        abort_unless(auth()->user()->can('update', $item), 403);

        $this->editingItemId = $item->id;
        $this->itemCategoryId = (string) $item->inventory_category_id;
        $this->itemName = $item->name;
        $this->itemDescription = $item->description ?? '';
        $this->itemIsUnique = $item->is_unique;
        $this->itemSerial = $item->serial ?? '';
        $this->itemQuantity = (string) $item->quantity;
        $this->itemStatus = $item->status->value;
        $this->itemLocation = $item->location ?? '';
        $this->itemNotes = $item->notes ?? '';
        $this->resetValidation();
        $this->statusMessage = '';
    }

    public function saveItem(): void
    {
        $data = $this->validate([
            'itemCategoryId' => ['required', 'exists:inventory_categories,id'],
            'itemName' => ['required', 'string', 'max:100'],
            'itemDescription' => ['nullable', 'string', 'max:500'],
            'itemIsUnique' => ['boolean'],
            'itemSerial' => ['nullable', 'string', 'max:60'],
            'itemQuantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'itemStatus' => ['required', 'in:'.implode(',', array_column($this->statuses, 'value'))],
            'itemLocation' => ['nullable', 'string', 'max:80'],
            'itemNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $attributes = [
            'inventory_category_id' => (int) $data['itemCategoryId'],
            'name' => $data['itemName'],
            'description' => $data['itemDescription'] ?: null,
            'is_unique' => $data['itemIsUnique'],
            'serial' => $data['itemSerial'] ?: null,
            // Una unidad con serie es siempre un solo objeto físico.
            'quantity' => $data['itemIsUnique'] ? 1 : $data['itemQuantity'],
            'status' => $data['itemStatus'],
            'location' => $data['itemLocation'] ?: null,
            'notes' => $data['itemNotes'] ?: null,
        ];

        $message = '';
        $wasEditing = $this->editingItemId !== null;

        if ($wasEditing) {
            $item = InventoryItem::query()->findOrFail($this->editingItemId);

            abort_unless(auth()->user()->can('update', $item), 403);

            $item->update($attributes);
            $message = 'Artículo «'.$item->name.'» actualizado.';
        } else {
            abort_unless(auth()->user()->can('create', InventoryItem::class), 403);

            $item = InventoryItem::query()->create($attributes + ['created_by' => auth()->id()]);
            $message = 'Artículo «'.$item->name.'» registrado.';
        }

        $this->resetItemForm();
        $this->statusMessage = $message;
        unset($this->items, $this->totalQuantity, $this->categories);
    }

    public function deleteItem(int $itemId): void
    {
        $item = InventoryItem::query()->findOrFail($itemId);

        abort_unless(auth()->user()->can('delete', $item), 403);

        $name = $item->name;
        $item->delete();

        $this->resetItemForm();
        $this->statusMessage = 'Artículo «'.$name.'» eliminado.';
        unset($this->items, $this->totalQuantity, $this->categories);
    }

    public function resetCategoryForm(): void
    {
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->categoryDescription = '';
        $this->resetValidation();
    }

    public function editCategory(int $categoryId): void
    {
        $category = InventoryCategory::query()->findOrFail($categoryId);

        abort_unless(auth()->user()->can('update', $category), 403);

        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryDescription = $category->description ?? '';
        $this->resetValidation();
    }

    public function saveCategory(): void
    {
        $data = $this->validate([
            'categoryName' => [
                'required', 'string', 'max:60',
                Rule::unique('inventory_categories', 'name')->ignore($this->editingCategoryId),
            ],
            'categoryDescription' => ['nullable', 'string', 'max:200'],
        ]);

        $attributes = [
            'name' => $data['categoryName'],
            'description' => $data['categoryDescription'] ?: null,
        ];

        if ($this->editingCategoryId !== null) {
            $category = InventoryCategory::query()->findOrFail($this->editingCategoryId);

            abort_unless(auth()->user()->can('update', $category), 403);

            $category->update($attributes);
            $this->statusMessage = 'Categoría «'.$category->name.'» actualizada.';
        } else {
            abort_unless(auth()->user()->can('create', InventoryCategory::class), 403);

            $category = InventoryCategory::query()->create($attributes);
            $this->statusMessage = 'Categoría «'.$category->name.'» creada.';
        }

        $this->resetCategoryForm();
        unset($this->items, $this->totalQuantity, $this->categories);
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = InventoryCategory::query()->findOrFail($categoryId);

        abort_unless(auth()->user()->can('delete', $category), 403);

        $name = $category->name;
        $category->delete();

        if ($this->selectedCategoryId === $categoryId) {
            $this->selectedCategoryId = null;
        }

        $this->statusMessage = 'Categoría «'.$name.'» eliminada junto con sus artículos.';
        unset($this->items, $this->totalQuantity, $this->categories);
    }

    public function exportFilters(): array
    {
        return [
            'categoria' => $this->selectedCategoryId ?? '',
            'estado' => $this->statusFilter,
        ];
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <a
                href="{{ route('it-desk.dashboard') }}"
                wire:navigate
                class="inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
            >
                <x-icon name="arrow-left" class="h-4 w-4" />
                IT Desk
            </a>

            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Inventario</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Control de lo que hay en el área: productos en caja, equipos dañados, herramientas y más.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <span class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink-muted uppercase">
                <x-icon name="chart" class="h-4 w-4" />
                {{ $this->totalQuantity }} unidades · {{ $this->items->count() }} registros
            </span>

            <a
                href="{{ route('it-desk.inventory.export.pdf', $this->exportFilters()) }}"
                target="_blank"
                rel="noopener"
                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line bg-surface px-4 text-sm font-semibold text-ink transition duration-fast ease-signal hover:bg-sunken"
            >
                <x-icon name="external" class="h-4 w-4" />
                Informe PDF
            </a>

            <a
                href="{{ route('it-desk.inventory.export.word', $this->exportFilters()) }}"
                class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
            >
                <x-icon name="book" class="h-4 w-4" />
                Informe Word
            </a>
        </div>
    </header>

    @if ($statusMessage !== '')
        <p class="mt-4 border-l-2 border-brand bg-brand-soft px-3 py-2 text-sm text-brand-strong" role="status">
            {{ $statusMessage }}
        </p>
    @endif

    <div class="mt-6 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="lg:col-span-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label class="flex-1">
                    <span class="sr-only">Buscar artículo</span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por nombre, serie o ubicación…"
                        class="w-full rounded-md border-line bg-surface py-2.5 text-sm focus:border-accent focus:ring-accent"
                    />
                </label>

                <label class="sm:w-52">
                    <span class="sr-only">Filtrar por estado</span>
                    <select
                        wire:model.live="statusFilter"
                        class="w-full rounded-md border-line bg-surface py-2.5 text-sm focus:border-accent focus:ring-accent"
                    >
                        <option value="">Todos los estados</option>
                        @foreach ($this->statuses as $status)
                            <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="mt-3 flex flex-wrap gap-2" role="group" aria-label="Filtrar por categoría">
                <button
                    type="button"
                    wire:click="selectCategory(null)"
                    @class([
                        'inline-flex min-h-9 items-center rounded-full border px-3 text-xs font-semibold tracking-label uppercase transition duration-fast ease-signal',
                        'border-ink bg-ink text-canvas' => $selectedCategoryId === null,
                        'border-line text-ink-muted hover:text-ink' => $selectedCategoryId !== null,
                    ])
                >
                    Todas
                </button>

                @foreach ($this->categories as $category)
                    <button
                        type="button"
                        wire:click="selectCategory({{ $category->id }})"
                        @class([
                            'inline-flex min-h-9 items-center gap-1.5 rounded-full border px-3 text-xs font-semibold tracking-label uppercase transition duration-fast ease-signal',
                            'border-ink bg-ink text-canvas' => $selectedCategoryId === $category->id,
                            'border-line text-ink-muted hover:text-ink' => $selectedCategoryId !== $category->id,
                        ])
                    >
                        {{ $category->name }}
                        <span class="tabular-nums opacity-60">{{ $category->items_count }}</span>
                    </button>
                @endforeach
            </div>

            <section class="mt-4 divide-y divide-line border border-line bg-surface" aria-label="Artículos del inventario">
                @forelse ($this->items as $item)
                    <article class="flex flex-wrap items-start gap-3 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-ink">
                                {{ $item->name }}
                                @if ($item->is_unique)
                                    <span class="text-3xs font-semibold tracking-label text-ink-muted uppercase">· con serie</span>
                                @endif
                            </h3>

                            @if ($item->description)
                                <p class="mt-0.5 text-xs text-ink-muted">{{ $item->description }}</p>
                            @endif

                            <p class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-3xs font-semibold tracking-label text-ink-muted uppercase">
                                <span>{{ $item->category->name }}</span>
                                @if ($item->serial)
                                    <span>Serie: {{ $item->serial }}</span>
                                @endif
                                @if ($item->location)
                                    <span>{{ $item->location }}</span>
                                @endif
                            </p>
                        </div>

                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-3xs font-semibold tracking-label uppercase {{ $item->status->badgeClasses() }}">
                            {{ $item->status->label() }}
                        </span>

                        <span class="min-w-14 text-right font-condensed text-lg font-black tabular-nums text-ink" title="Cantidad">
                            {{ $item->effectiveQuantity() }}
                        </span>

                        <div class="flex gap-1.5">
                            @can('update', $item)
                                <button
                                    type="button"
                                    wire:click="editItem({{ $item->id }})"
                                    aria-label="Editar artículo {{ $item->name }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-line text-ink transition duration-fast ease-signal hover:bg-sunken"
                                >
                                    <x-icon name="edit" class="h-4 w-4" />
                                </button>
                            @endcan

                            @can('delete', $item)
                                <button
                                    type="button"
                                    wire:click="deleteItem({{ $item->id }})"
                                    wire:confirm="¿Eliminar el artículo «{{ $item->name }}»?"
                                    aria-label="Eliminar artículo {{ $item->name }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-danger/30 text-danger transition duration-fast ease-signal hover:bg-danger-soft"
                                >
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            @endcan
                        </div>
                    </article>
                @empty
                    <p class="px-4 py-6 text-sm text-ink-muted">
                        No hay artículos con estos filtros. Registra el primero desde el formulario.
                    </p>
                @endforelse
            </section>
        </div>

        <aside class="lg:col-span-4">
            <form wire:submit="saveItem" class="border border-line bg-surface p-5 lg:sticky lg:top-20">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">
                        {{ $editingItemId ? 'Editar artículo' : 'Registrar artículo' }}
                    </h2>

                    @if ($editingItemId)
                        <button
                            type="button"
                            wire:click="resetItemForm"
                            class="text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
                        >
                            Cancelar
                        </button>
                    @endif
                </div>

                <div class="mt-4 space-y-4">
                    <div>
                        <label for="itemCategoryId" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Categoría</label>
                        <select
                            id="itemCategoryId"
                            wire:model="itemCategoryId"
                            class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                        >
                            <option value="">Selecciona una categoría</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('itemCategoryId') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="itemName" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Artículo</label>
                        <input
                            id="itemName"
                            type="text"
                            wire:model="itemName"
                            maxlength="100"
                            placeholder="Ej. Laptop Lenovo ThinkPad E14"
                            class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                        />
                        @error('itemName') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="itemDescription" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Descripción</label>
                        <textarea
                            id="itemDescription"
                            wire:model="itemDescription"
                            rows="2"
                            maxlength="500"
                            placeholder="Opcional: modelo, detalles del daño, etc."
                            class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-accent"
                        ></textarea>
                        @error('itemDescription') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-start gap-2.5">
                        <input type="checkbox" wire:model="itemIsUnique" class="mt-0.5 h-4 w-4 rounded border-line" />
                        <span class="text-xs text-ink-muted">
                            <span class="font-semibold text-ink">Artículo único con serie</span>
                            — laptops, monitores, etc. La cantidad siempre es 1.
                        </span>
                    </label>

                    @if ($itemIsUnique)
                        <div>
                            <label for="itemSerial" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Número de serie</label>
                            <input
                                id="itemSerial"
                                type="text"
                                wire:model="itemSerial"
                                maxlength="60"
                                placeholder="Opcional"
                                class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                            />
                            @error('itemSerial') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div>
                            <label for="itemQuantity" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Cantidad</label>
                            <input
                                id="itemQuantity"
                                type="number"
                                wire:model="itemQuantity"
                                min="1"
                                max="99999"
                                class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                            />
                            @error('itemQuantity') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label for="itemStatus" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Estado</label>
                        <select
                            id="itemStatus"
                            wire:model="itemStatus"
                            class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                        >
                            @foreach ($this->statuses as $status)
                                <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                            @endforeach
                        </select>
                        @error('itemStatus') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="itemLocation" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Ubicación</label>
                        <input
                            id="itemLocation"
                            type="text"
                            wire:model="itemLocation"
                            maxlength="80"
                            placeholder="Ej. Estante A, bodega central"
                            class="mt-1.5 w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                        />
                        @error('itemLocation') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="itemNotes" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Notas</label>
                        <textarea
                            id="itemNotes"
                            wire:model="itemNotes"
                            rows="2"
                            maxlength="500"
                            placeholder="Opcional"
                            class="mt-1.5 w-full rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink placeholder:text-ink-disabled focus:border-accent focus:ring-accent"
                        ></textarea>
                        @error('itemNotes') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-2">
                    <button
                        type="submit"
                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                    >
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $editingItemId ? 'Guardar cambios' : 'Registrar artículo' }}
                    </button>

                    <button
                        type="button"
                        wire:click="resetItemForm"
                        class="inline-flex min-h-10 items-center justify-center rounded-md border border-line px-4 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                    >
                        Limpiar formulario
                    </button>
                </div>
            </form>

            @if ($this->canManageCategories())
                <section class="mt-6 border border-line bg-surface p-5" aria-label="Gestión de categorías">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Categorías</h2>

                    <ul class="mt-3 divide-y divide-line">
                        @foreach ($this->categories as $category)
                            <li class="flex items-center gap-2 py-2">
                                <span class="min-w-0 flex-1 truncate text-xs font-semibold text-ink">
                                    {{ $category->name }}
                                </span>

                                <button
                                    type="button"
                                    wire:click="editCategory({{ $category->id }})"
                                    aria-label="Editar categoría {{ $category->name }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-line text-ink transition duration-fast ease-signal hover:bg-sunken"
                                >
                                    <x-icon name="edit" class="h-3.5 w-3.5" />
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteCategory({{ $category->id }})"
                                    wire:confirm="¿Eliminar «{{ $category->name }}» y sus {{ $category->items_count }} artículos?"
                                    aria-label="Eliminar categoría {{ $category->name }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-danger/30 text-danger transition duration-fast ease-signal hover:bg-danger-soft"
                                >
                                    <x-icon name="trash" class="h-3.5 w-3.5" />
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <form wire:submit="saveCategory" class="mt-4 space-y-3 border-t border-line pt-4">
                        <h3 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">
                            {{ $editingCategoryId ? 'Editar categoría' : 'Nueva categoría' }}
                        </h3>

                        <div>
                            <label for="categoryName" class="sr-only">Nombre de la categoría</label>
                            <input
                                id="categoryName"
                                type="text"
                                wire:model="categoryName"
                                maxlength="60"
                                placeholder="Ej. Productos en caja"
                                class="w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                            />
                            @error('categoryName') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>

                        <input
                            type="text"
                            wire:model="categoryDescription"
                            maxlength="200"
                            placeholder="Descripción opcional"
                            class="w-full rounded-md border-line bg-canvas py-2 text-sm focus:border-accent focus:ring-accent"
                        />
                        @error('categoryDescription') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-md border border-line bg-canvas px-4 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken"
                            >
                                {{ $editingCategoryId ? 'Guardar categoría' : 'Crear categoría' }}
                            </button>

                            @if ($editingCategoryId)
                                <button
                                    type="button"
                                    wire:click="resetCategoryForm"
                                    class="inline-flex min-h-10 items-center justify-center rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:bg-sunken"
                                >
                                    Cancelar
                                </button>
                            @endif
                        </div>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</div>
