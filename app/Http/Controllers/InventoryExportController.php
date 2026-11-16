<?php

namespace App\Http\Controllers;

use App\Enums\InventoryItemStatus;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class InventoryExportController extends Controller
{
    /**
     * Informe del inventario en PDF (dompdf).
     */
    public function pdf(Request $request): Response
    {
        $this->authorizeInventory($request->user());

        $data = $this->collectReportData($request);
        $pdf = Pdf::loadView('exports.inventory-report', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download($this->filename('pdf'));
    }

    /**
     * Informe del inventario en Word (.docx, editable).
     */
    public function word(Request $request): BinaryFileResponse
    {
        $this->authorizeInventory($request->user());

        $data = $this->collectReportData($request);

        $word = new PhpWord;
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(10);

        $section = $word->addSection();
        $section->addText('Inventario · IT Desk', ['bold' => true, 'size' => 20]);
        $section->addText($this->reportSubtitle($data['category'], $data['status']), ['size' => 10, 'color' => '666666']);
        $section->addText('Generado el '.now()->locale('es')->translatedFormat('d \d\e F \d\e Y'), ['size' => 9, 'color' => '666666']);
        $section->addTextBreak(1);

        foreach ($data['categories'] as $group) {
            $section->addText($group['category']->name, ['bold' => true, 'size' => 13]);
            $section->addTextBreak(1);

            $table = $section->addTable([
                'cellMargin' => 60,
            ]);
            $table->addRow();
            foreach (['Artículo', 'Estado', 'Cantidad', 'Serie', 'Ubicación'] as $header) {
                $table->addCell(2600)->addText($header, ['bold' => true, 'size' => 9]);
            }

            foreach ($group['items'] as $item) {
                $table->addRow();
                $table->addCell(2600)->addText($item->name, ['size' => 9]);
                $table->addCell(1300)->addText($item->status->label(), ['size' => 9]);
                $table->addCell(900)->addText((string) $item->effectiveQuantity(), ['size' => 9]);
                $table->addCell(1500)->addText($item->serial ?? '—', ['size' => 9]);
                $table->addCell(1400)->addText($item->location ?? '—', ['size' => 9]);
            }

            $section->addText('Subtotal: '.$group['totalQuantity'].' unidades en '.$group['items']->count().' registros', ['italic' => true, 'size' => 9]);
            $section->addTextBreak(1);
        }

        $section->addTextBreak(1);
        $section->addText(
            'Total general: '.$data['grandTotalQuantity'].' unidades en '.$data['grandTotalItems'].' registros',
            ['bold' => true, 'size' => 11]
        );

        $tempPath = storage_path('framework/'.$this->filename('docx'));
        $word->save($tempPath, 'Word2007');

        return response()
            ->download($tempPath, $this->filename('docx'))
            ->deleteFileAfterSend(true);
    }

    /**
     * @return array{category: ?InventoryCategory, status: ?InventoryItemStatus, categories: Collection, grandTotalQuantity: int, grandTotalItems: int}
     */
    private function collectReportData(Request $request): array
    {
        $categoryId = (int) $request->query('categoria');
        $statusValue = $request->query('estado');

        $category = $categoryId !== 0
            ? InventoryCategory::query()->findOrFail($categoryId)
            : null;

        $status = $statusValue !== null && $statusValue !== ''
            ? InventoryItemStatus::from((string) $statusValue)
            : null;

        $query = InventoryItem::query()
            ->with('category')
            ->when($category !== null, fn ($q) => $q->where('inventory_category_id', $category->id))
            ->when($status !== null, fn ($q) => $q->where('status', $status->value))
            ->orderBy('name');

        $items = $query->get();

        $groups = $items
            ->groupBy('inventory_category_id')
            ->map(fn ($groupItems) => [
                'category' => $groupItems->first()->category,
                'items' => $groupItems,
                'totalQuantity' => $groupItems->sum(fn (InventoryItem $item): int => $item->effectiveQuantity()),
            ])
            ->sortBy(fn (array $group): string => $group['category']->name)
            ->values();

        return [
            'category' => $category,
            'status' => $status,
            'categories' => $groups,
            'grandTotalQuantity' => $items->sum(fn (InventoryItem $item): int => $item->effectiveQuantity()),
            'grandTotalItems' => $items->count(),
        ];
    }

    private function reportSubtitle(?InventoryCategory $category, ?InventoryItemStatus $status): string
    {
        $parts = [];

        $parts[] = $category !== null ? 'Categoría: '.$category->name : 'Todas las categorías';

        if ($status !== null) {
            $parts[] = 'Estado: '.$status->label();
        }

        return implode(' · ', $parts);
    }

    private function filename(string $extension): string
    {
        return 'informe-inventario-'.now()->format('Y-m-d').'.'.$extension;
    }

    private function authorizeInventory(User $user): void
    {
        abort_unless($user->can('viewAny', InventoryItem::class), 403);
    }
}
