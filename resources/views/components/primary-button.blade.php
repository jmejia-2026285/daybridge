<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-md bg-accent px-4 py-2 text-xs font-semibold tracking-label text-accent-foreground uppercase transition duration-fast ease-signal hover:bg-accent/90 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
