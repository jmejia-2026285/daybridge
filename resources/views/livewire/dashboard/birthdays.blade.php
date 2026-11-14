<div style="background-color: rgba(15, 15, 15, 0.75); backdrop-filter: blur(16px); border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.1); color: #ffffff;" class="p-5 shadow-xl">
    <h3 class="text-lg font-bold mb-3 border-b border-white/10 pb-2">Cumpleaños del Mes</h3>
    <ul class="space-y-2">
        @foreach($birthdays as $person)
            <li class="flex justify-between text-sm py-1">
                <span>{{ $person->name }}</span>
                <span class="text-slate-300">{{ $person->date }}</span>
            </li>
        @endforeach
    </ul>
</div>
