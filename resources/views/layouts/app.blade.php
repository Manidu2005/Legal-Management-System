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
    <body class="bg-mesh-light min-h-screen text-slate-700 font-sans antialiased selection:bg-indigo-100 selection:text-indigo-900">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="pt-8 pb-4">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="glass px-6 py-4 rounded-2xl shadow-sm border border-white/40 flex justify-between items-center animate-fade-in-up">
                        {{ $header }}
                    </div>
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 animate-fade-in-up stagger-1">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
