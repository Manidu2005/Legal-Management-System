<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="heading-display !text-3xl text-mist-950 dark:text-white">
                {{ __('Welcome back, :name', ['name' => explode(' ', Auth::user()->name)[0]]) }}
            </h2>
            <p class="text-mist-500 dark:text-mist-400 mt-1 font-medium tracking-wide flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-mist-500 animate-pulse"></span>
                {{ ucfirst(Auth::user()->role) }} {{ __('Workspace') }}
            </p>
        </div>
        <div class="hidden sm:block text-right">
            <p class="text-sm font-semibold text-mist-700 dark:text-mist-300">{{ now()->format('l, jS F Y') }}</p>
            <p class="text-xs text-mist-400 font-medium tracking-wider uppercase mt-0.5">{{ __("Today's Overview") }}</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        @foreach([
            ['Total Cases', $totalCases ?? 0, __('Active matters on record'), 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
            ['Court Dates', $upcomingCourtDates ?? 0, __('Upcoming proceedings'), 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['Active Clients', $activeClients ?? 0, __('Registered profiles'), 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['Documents', $totalDocuments ?? 0, __('Files in secure vault'), 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ] as [$label, $value, $desc, $iconPath])
        <div class="glass-card p-6 hover-lift group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-mist-950/5 dark:bg-white/10 text-mist-700 dark:text-mist-300 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-mist-500 uppercase tracking-wider">{{ __($label) }}</p>
                    <h3 class="font-sans font-semibold text-3xl text-mist-950 dark:text-white">{{ $value }}</h3>
                </div>
            </div>
            <div class="text-sm text-mist-600 dark:text-mist-400 font-medium flex items-center gap-1">
                {{ $desc }} <span class="group-hover:translate-x-1 transition-transform">→</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-6">
            <div class="glass-card p-6">
                <h3 class="heading-section !text-xl mb-6">{{ __('Quick Actions') }}</h3>
                <div class="space-y-3">
                    @foreach([
                        [route('cases.create'), __('Open new case'), 'M12 4.5v15m7.5-7.5h-15'],
                        [route('clients.create'), __('Register client'), 'M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z'],
                        [route('documents.create'), __('Upload document'), 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5'],
                    ] as [$url, $text, $iconPath])
                    <a href="{{ $url }}" class="group flex items-center justify-between p-4 rounded-xl bg-mist-950/5 dark:bg-white/5 hover:bg-mist-950/10 dark:hover:bg-white/10 border border-mist-950/10 dark:border-white/10 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-mist-100 dark:bg-mist-900 flex items-center justify-center text-mist-700 dark:text-mist-300">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}" />
                                </svg>
                            </div>
                            <span class="font-medium text-mist-800 dark:text-mist-200 group-hover:text-mist-950 dark:group-hover:text-white">{{ $text }}</span>
                        </div>
                        <span class="text-mist-400 group-hover:text-mist-950 dark:group-hover:text-white group-hover:translate-x-1 transition-all">→</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="glass-card p-6 h-full">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="heading-section !text-xl">{{ __('Workspace Modules') }}</h3>
                    <div class="px-3 py-1 bg-mist-950/5 dark:bg-white/10 text-mist-600 dark:text-mist-300 rounded-full text-xs font-semibold uppercase tracking-wider">{{ __('Explore') }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach([
                        ['Cases', route('cases.index'), 'Registry & Status'],
                        ['Clients', route('clients.index'), 'Records & NICs'],
                        ['Documents', route('documents.index'), 'File Vault'],
                        ['Scheduling', route('court-dates.index'), 'Reminders'],
                    ] as [$label, $url, $desc])
                    <a href="{{ $url }}" class="group block p-5 rounded-2xl border border-mist-950/10 dark:border-white/10 bg-mist-950/2.5 dark:bg-white/5 hover:bg-mist-950/5 dark:hover:bg-white/10 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-sans font-semibold text-lg text-mist-950 dark:text-white group-hover:opacity-80 transition-opacity">{{ __($label) }}</h4>
                            <div class="w-8 h-8 rounded-full bg-mist-950/5 dark:bg-white/10 flex items-center justify-center text-mist-500 dark:text-mist-400 group-hover:text-mist-950 dark:group-hover:text-white transition-colors">
                                <svg class="w-4 h-4 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-sm text-mist-500 dark:text-mist-400">{{ __($desc) }}</p>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
