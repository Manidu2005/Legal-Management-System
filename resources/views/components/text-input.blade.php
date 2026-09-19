@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-mist-950/10 dark:border-white/10 dark:bg-mist-900 dark:text-white dark:placeholder-mist-400 focus:border-mist-950 dark:focus:border-white focus:ring-mist-500/20 dark:focus:ring-mist-500/20 rounded-md shadow-sm']) }}>
