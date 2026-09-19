<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-transparent border border-mist-950/10 dark:border-white/10 rounded-full font-semibold text-xs text-mist-950 dark:text-white uppercase tracking-widest hover:bg-mist-950/10 dark:hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-mist-500/20 focus:ring-offset-2 dark:focus:ring-offset-mist-950 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
