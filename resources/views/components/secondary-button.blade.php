<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-md border border-line bg-surface px-4 py-2 text-xs font-semibold tracking-label text-ink uppercase transition duration-fast ease-signal hover:bg-sunken disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
