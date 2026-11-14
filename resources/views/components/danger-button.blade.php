<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-md bg-danger px-4 py-2 text-xs font-semibold tracking-label text-ink-inverse uppercase transition duration-fast ease-signal hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
