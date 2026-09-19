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
    <body class="bg-mist-100 dark:bg-mist-950 min-h-screen flex items-center justify-center p-6 font-sans antialiased text-mist-950 dark:text-white">

        <div class="w-full max-w-md animate-fade-in-up">
            <div class="text-center mb-8">
                <a href="/" class="inline-block text-4xl font-sans font-semibold tracking-tight text-mist-950 dark:text-white">
                    LexLanka
                </a>
                <p class="mt-2 text-sm text-mist-500 dark:text-mist-400 font-medium tracking-wide uppercase">Legal Practice Management</p>
            </div>

            <div class="glass-card p-8">
                {{ $slot }}
            </div>
        </div>

    </body>
</html>
