@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-mist-950 dark:border-white text-start text-base font-medium text-mist-950 dark:text-white bg-mist-950/10 dark:bg-white/10 focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-mist-600 dark:text-mist-400 hover:text-mist-950 dark:hover:text-white hover:bg-mist-950/10 dark:hover:bg-white/10 hover:border-mist-300 dark:hover:border-mist-600 focus:outline-none focus:text-mist-950 dark:focus:text-white focus:bg-mist-950/10 dark:focus:bg-white/10 focus:border-mist-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
