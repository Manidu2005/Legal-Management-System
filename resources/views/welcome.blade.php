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
<body class="bg-mesh-dark min-h-screen font-sans text-slate-200 antialiased selection:bg-indigo-500/30 selection:text-white flex flex-col">

    <!-- Top Navigation -->
    <header class="w-full absolute top-0 z-50 py-6 px-8 flex justify-between items-center animate-fade-in-up">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-sky-400 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                </svg>
            </div>
            <span class="font-heading font-bold text-2xl tracking-tight text-white">LexLanka</span>
        </div>
        
        <div class="flex gap-4 items-center">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn-primary">Go to Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-300 hover:text-white transition-colors">Sign in</a>

            @endauth
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-grow flex items-center justify-center relative overflow-hidden">
        <!-- Floating Orbs for effect -->
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-sky-500/20 rounded-full blur-3xl"></div>

        <div class="max-w-5xl mx-auto px-6 relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            
            <!-- Left Text -->
            <div class="animate-fade-in-up stagger-1">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/10 text-sky-300 text-sm font-medium mb-6 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
                    Modern Legal OS
                </div>
                <h1 class="font-heading font-bold text-5xl lg:text-7xl leading-tight tracking-tight text-white mb-6">
                    Manage your practice with <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-sky-400">precision.</span>
                </h1>
                <p class="text-lg text-slate-400 mb-8 leading-relaxed max-w-lg">
                    LexLanka is the complete operating system for modern law firms. Cases, schedules, client records, and financials—all in one beautifully crafted workspace.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('login') }}" class="btn-primary text-lg px-8 py-3.5">Get Started</a>
                    <a href="#features" class="btn-glass text-white text-lg px-8 py-3.5 border border-white/20">Learn More</a>
                </div>
            </div>

            <!-- Right Animation -->
            <div class="animate-fade-in-up stagger-2 hidden lg:flex justify-center items-center">
                <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
                <dotlottie-player src="{{ asset('animations/UsYm3hHylE.lottie') }}" background="transparent" speed="1" style="width: 500px; height: 500px;" loop autoplay class="w-full max-w-lg transform hover:scale-105 transition-transform duration-500"></dotlottie-player>
            </div>

        </div>
    </main>

</body>
</html>
