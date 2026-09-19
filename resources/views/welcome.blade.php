<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LexLanka — Legal Practice Management</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-mist-950 min-h-screen font-sans text-white antialiased selection:bg-mist-700 selection:text-white flex flex-col">

    <header class="w-full py-6 px-8 flex justify-between items-center animate-fade-in-up border-b border-white/10">
        <a href="/" class="font-sans font-semibold text-2xl tracking-tight text-white hover:opacity-80 transition-opacity">
            LexLanka
        </a>

        <div class="flex gap-4 items-center">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary">Go to Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="text-sm font-medium text-mist-300 hover:text-white transition-colors">Sign in</a>
            @endauth
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center px-6 py-16">
        <div class="max-w-3xl mx-auto text-center animate-fade-in-up stagger-1">
            <p class="text-sm font-medium tracking-wide uppercase text-mist-400 mb-6">
                Legal Practice Management
            </p>
            <h1 class="font-sans font-semibold text-5xl lg:text-7xl leading-tight tracking-tight text-white mb-6">
                Manage your practice with precision.
            </h1>
            <p class="text-lg text-mist-400 mb-10 leading-relaxed max-w-2xl mx-auto">
                LexLanka is the complete operating system for modern law firms. Cases, schedules, client records, and financials — all in one thoughtfully crafted workspace.
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-3.5 rounded-full bg-mist-300 text-mist-950 font-medium hover:bg-mist-200 transition-colors">
                    Get Started
                </a>
                <a href="#features" class="inline-flex items-center justify-center px-8 py-3.5 rounded-full border border-white/20 text-white font-medium hover:bg-white/10 transition-colors">
                    Learn More
                </a>
            </div>
        </div>
    </main>

</body>
</html>
