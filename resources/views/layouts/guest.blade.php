<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'LexLanka') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-mesh-light min-h-screen flex items-center justify-center p-6 text-slate-700 font-sans antialiased">
        
        <div class="w-full max-w-md animate-fade-in-up">
            <!-- Brand -->
            <div class="text-center mb-8">
                <a href="/" class="inline-flex items-center justify-center gap-2 text-3xl font-heading font-bold tracking-tight text-slate-900">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-sky-400 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                    </div>
                    LexLanka
                </a>
                <p class="mt-2 text-sm text-slate-500 font-medium tracking-wide uppercase">Legal Practice Management</p>
            </div>

            <!-- Glass Card -->
            <div class="glass-card p-8 backdrop-blur-xl">
                {{ $slot }}
            </div>
        </div>
        
    </body>
</html>
