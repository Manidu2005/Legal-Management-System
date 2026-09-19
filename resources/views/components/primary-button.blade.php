<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-mist-950 dark:bg-mist-300 border border-transparent rounded-full font-semibold text-xs text-white dark:text-mist-950 uppercase tracking-widest hover:bg-mist-800 dark:hover:bg-mist-200 focus:bg-mist-800 dark:focus:bg-mist-200 active:bg-mist-900 dark:active:bg-mist-400 focus:outline-none focus:ring-2 focus:ring-mist-500/20 focus:ring-offset-2 dark:focus:ring-offset-mist-950 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
