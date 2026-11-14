<?php

use App\Models\Equipment;
use App\Models\Ticket;
use App\Services\ItDesk\TicketService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.print')] #[Title('Imprimir tickets · IT Desk')] class extends Component
{
    public Equipment $equipment;

    public Ticket $ticket;

    public bool $printed = false;

    public function mount(Equipment $equipment, ?Ticket $ticket = null): void
    {
        $ticket ??= $equipment->tickets()->latest('id')->firstOrFail();

        abort_unless($ticket->equipment_id === $equipment->id, 404);
        abort_unless(auth()->user()->can('print', $ticket), 403);

        $this->equipment = $equipment->load('receivedBy');
        $this->ticket = $ticket->load('assignedTo');
        $this->printed = $ticket->isPrinted();
    }

    public function print(TicketService $ticketService): void
    {
        abort_unless(auth()->user()->can('print', $this->ticket), 403);

        $ticketService->markPrinted($this->ticket, auth()->user());

        $this->ticket->refresh();
        $this->printed = true;

        $this->dispatch('print-ticket');
    }
}; ?>

<div>
    <div class="print-hidden mx-auto flex max-w-[11in] flex-wrap items-end justify-between gap-4 px-4 pb-4 pt-6 sm:px-6">
        <div>
            <a
                href="{{ route('it-desk.equipment.show', $equipment) }}"
                wire:navigate
                class="inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
            >
                <x-icon name="arrow-left" class="h-4 w-4" />
                Volver al equipo
            </a>

            <h1 class="mt-2 font-expanded text-2xl font-black tracking-display">Imprimir tickets</h1>
            <p class="mt-1 text-sm text-ink-muted">
                {{ $equipment->code }} · {{ $ticket->folio }} · Dos copias por hoja carta horizontal.
            </p>
        </div>

        <div class="flex flex-col items-stretch gap-2 sm:items-end">
            <button
                type="button"
                wire:click="print"
                wire:loading.attr="disabled"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong disabled:cursor-wait disabled:opacity-70"
            >
                <x-icon name="printer" class="h-4 w-4" />
                {{ $printed ? 'Imprimir de nuevo' : 'Imprimir 2 copias' }}
            </button>

            <p class="max-w-[22rem] text-xs text-ink-muted sm:text-right">
                Al imprimir se registra una copia de equipo y una de cliente en la bitácora del ticket.
            </p>
        </div>
    </div>

    @if ($printed)
        <p class="print-hidden mx-auto max-w-[11in] px-4 pb-2 text-sm text-success sm:px-6" role="status">
            Impresión registrada. Última salida: {{ $ticket->printed_at?->locale('es')->translatedFormat('d M Y, H:i') }}.
        </p>
    @endif

    <main class="print-sheet mx-auto grid max-w-[11in] gap-4 px-4 pb-10 sm:px-6 lg:grid-cols-2">
        <article class="print-ticket flex min-h-[7.2in] flex-col border border-[#CBD2DC] bg-white p-4 text-[#111827] shadow-overlay">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#0A64FF]">Ticket de equipo</p>

            <div class="mt-2">
                <p class="text-[9px] uppercase tracking-wide text-[#6B7280]">Código interno</p>
                <p class="font-condensed text-3xl font-black tabular-nums">{{ $equipment->code }}</p>
            </div>

            <div class="mt-3 text-center">
                <x-barcode :value="$ticket->folio" width="330" height="70" class="h-[70px] w-full max-w-[330px]" />
                <p class="mt-1 font-condensed text-[12px] font-bold tabular-nums">{{ $ticket->folio }}</p>
                <p class="text-[9px] uppercase tracking-wide text-[#6B7280]">Número de seguimiento</p>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-x-3 gap-y-2.5 text-[10px]">
                <div>
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Fecha de ingreso</dt>
                    <dd class="font-bold">{{ $ticket->opened_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Estado</dt>
                    <dd class="font-bold">{{ $ticket->status->value }}</dd>
                </div>
                <div>
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Cliente</dt>
                    <dd class="font-bold">{{ $equipment->client_name }}</dd>
                </div>
                <div>
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Teléfono</dt>
                    <dd class="font-bold">{{ $equipment->client_phone }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Equipo</dt>
                    <dd class="font-bold">{{ $equipment->displayName() }}</dd>
                </div>
            </dl>

            <div class="mt-auto border-t border-[#E5E7EB] pt-2 text-[9px] text-[#6B7280]">
                IT Desk · Daybridge
            </div>
        </article>

        <article class="print-ticket flex min-h-[7.2in] flex-col border border-[#CBD2DC] bg-white p-4 text-[#111827] shadow-overlay">
            <header class="flex items-start justify-between gap-3 border-b border-[#E5E7EB] pb-2">
                <img
                    src="{{ asset('media/it-desk/01_logo_horizontal_transparente.png') }}"
                    alt="IT Desk"
                    class="h-7 w-auto"
                >

                <div class="text-right">
                    <p class="text-[9px] uppercase tracking-wide text-[#6B7280]">Folio</p>
                    <p class="font-condensed text-[12px] font-bold tabular-nums">{{ $ticket->folio }}</p>
                </div>
            </header>

            <p class="mt-2 text-[10px] font-bold uppercase tracking-[0.18em] text-[#0A64FF]">Comprobante de ingreso</p>

            <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-2 text-[10px]">
                <div>
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Fecha y hora de ingreso</dt>
                    <dd class="font-bold">{{ $ticket->opened_at->format('d/m/Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Fecha estimada de entrega</dt>
                    <dd class="font-bold">{{ $equipment->estimated_delivery_date?->format('d/m/Y') ?? 'Por confirmar' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Cliente</dt>
                    <dd class="font-bold">{{ $equipment->client_name }}</dd>
                </div>
                <div>
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Teléfono</dt>
                    <dd class="font-bold">{{ $equipment->client_phone }}</dd>
                </div>
                <div>
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Correo</dt>
                    <dd class="font-bold">{{ $equipment->client_email ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Equipo</dt>
                    <dd class="font-bold">{{ $equipment->displayName() }}</dd>
                </div>
                <div>
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Número de serie</dt>
                    <dd class="font-bold">{{ $equipment->serial ?: '—' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Motivo de la falla</dt>
                    <dd class="font-bold">{{ $equipment->issue }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Accesorios dejados</dt>
                    <dd class="font-bold">{{ $equipment->accessories ?: '—' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Diagnóstico inicial</dt>
                    <dd class="font-bold">{{ $equipment->diagnosis ?: '—' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Técnico que recibe</dt>
                    <dd class="font-bold">{{ $equipment->receivedBy?->name ?? '—' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-[9px] uppercase tracking-wide text-[#6B7280]">Notas adicionales</dt>
                    <dd class="font-bold">{{ $equipment->notes ?: '—' }}</dd>
                </div>
            </dl>

            <p class="mt-auto border-t border-[#E5E7EB] pt-2 text-[9px] leading-relaxed text-[#6B7280]">
                Conserve este comprobante: lo necesitará para retirar su equipo.
            </p>
        </article>
    </main>

    <script>
        window.addEventListener('print-ticket', () => {
            setTimeout(() => window.print(), 150);
        });
    </script>
</div>
