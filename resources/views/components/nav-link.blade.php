@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-mist-950 dark:border-white text-sm font-medium leading-5 text-mist-950 dark:text-white focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-mist-500 dark:text-mist-400 hover:text-mist-950 dark:hover:text-white hover:border-mist-300 dark:hover:border-mist-600 focus:outline-none focus:text-mist-950 dark:focus:text-white focus:border-mist-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
