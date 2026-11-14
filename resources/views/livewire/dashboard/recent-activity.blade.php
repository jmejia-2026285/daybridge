<div class="bg-neutral-900/70 backdrop-blur-md border border-white/10 rounded-xl p-4 shadow-lg text-white">
    <div class="flex justify-between items-center">
        <span class="font-semibold text-sm">Ticket #{{ $ticket->id }}</span>
        <span class="text-xs text-slate-400">{{ $ticket->created_at->diffForHumans() }}</span>
    </div>
    <p class="text-slate-200 mt-2 text-sm">{{ $ticket->description }}</p>
</div>
