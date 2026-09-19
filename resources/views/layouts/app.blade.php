<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'LexLanka') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        {{-- Anti-flash: apply dark class before CSS paints --}}
        <script>
            (function(){
                const t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-mist-100 dark:bg-mist-950 min-h-screen font-sans antialiased text-mist-950 dark:text-white selection:bg-mist-200 selection:text-mist-950 dark:selection:bg-mist-700 dark:selection:text-white">
        @include('layouts.navigation')

        @isset($header)
            <header class="pt-8 pb-4 border-b border-mist-950/10 dark:border-white/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center animate-fade-in-up">
                        {{ $header }}
                    </div>
                </div>
            </header>
        @endisset

        <main class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 animate-fade-in-up stagger-1">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
