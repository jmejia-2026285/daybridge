<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario · IT Desk</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #020C14;
            font-size: 10px;
            margin: 24px 18px;
        }

        .header {
            border-bottom: 3px solid #B39051;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0 0 4px;
            font-weight: 900;
        }

        .header p {
            margin: 0;
            font-size: 9px;
            color: #5A6570;
        }

        .category-title {
            font-size: 12px;
            font-weight: bold;
            margin: 12px 0 4px;
            color: #051623;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        th {
            background-color: #051623;
            color: #FFFFFF;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 6px;
            text-align: left;
        }

        td {
            border-bottom: 1px solid #DDE3E8;
            padding: 4px 6px;
            font-size: 9px;
        }

        .subtotal {
            font-size: 8px;
            font-style: italic;
            color: #5A6570;
            margin-bottom: 6px;
        }

        .totals {
            margin-top: 14px;
            border-top: 2px solid #B39051;
            padding-top: 6px;
            font-size: 11px;
            font-weight: bold;
        }

        .empty {
            font-style: italic;
            color: #5A6570;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Inventario · IT Desk</h1>
        <p>
            {{ $category !== null ? 'Categoría: '.$category->name : 'Todas las categorías' }}
            @if ($status !== null)
                · Estado: {{ $status->label() }}
            @endif
        </p>
        <p>Generado el {{ now()->locale('es')->translatedFormat('d \d\e F \d\e Y \a \l\a\s H:i') }}</p>
    </div>

    @forelse ($categories as $group)
        <p class="category-title">{{ $group['category']->name }}</p>
        <table>
            <thead>
                <tr>
                    <th style="width: 30%">Artículo</th>
                    <th style="width: 15%">Estado</th>
                    <th style="width: 9%">Cant.</th>
                    <th style="width: 18%">Serie</th>
                    <th style="width: 17%">Ubicación</th>
                    <th style="width: 11%">Registró</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['items'] as $item)
                    <tr>
                        <td>
                            {{ $item->name }}
                            @if ($item->description)
                                <br><span style="color: #5A6570; font-size: 8px">{{ $item->description }}</span>
                            @endif
                        </td>
                        <td>{{ $item->status->label() }}</td>
                        <td>{{ $item->effectiveQuantity() }}</td>
                        <td>{{ $item->serial ?? '—' }}</td>
                        <td>{{ $item->location ?? '—' }}</td>
                        <td>{{ $item->createdBy?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="subtotal">
            Subtotal: {{ $group['totalQuantity'] }} unidades en {{ $group['items']->count() }} registros
        </p>
    @empty
        <p class="empty">No hay artículos registrados con estos filtros.</p>
    @endforelse

    @if ($categories->isNotEmpty())
        <p class="totals">
            Total general: {{ number_format($grandTotalQuantity) }} unidades en {{ $grandTotalItems }} registros
        </p>
    @endif
</body>
</html>
