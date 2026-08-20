<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="heading-display !text-3xl text-slate-800">
                {{ __('Welcome back, :name', ['name' => explode(' ', Auth::user()->name)[0]]) }}
            </h2>
            <p class="text-slate-500 mt-1 font-medium tracking-wide flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ ucfirst(Auth::user()->role) }} {{ __('Workspace') }}
            </p>
        </div>
        <div class="hidden sm:block text-right">
            <p class="text-sm font-semibold text-slate-600">{{ now()->format('l, jS F Y') }}</p>
            <p class="text-xs text-slate-400 font-medium tracking-wider uppercase mt-0.5">{{ __("Today's Overview") }}</p>
        </div>
    </x-slot>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Stat Card 1 -->
        <div class="glass-card p-6 hover-lift border border-white/50 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-colors duration-500"></div>
            <div class="flex items-center gap-4 mb-4 relative z-10">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider">{{ __('Total Cases') }}</p>
                    <h3 class="font-heading font-bold text-3xl text-slate-800">{{ $totalCases ?? 0 }}</h3>
                </div>
            </div>
            <div class="relative z-10 text-sm text-indigo-600 font-medium flex items-center gap-1">
                {{ __('Active matters on record') }} <span class="group-hover:translate-x-1 transition-transform">→</span>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="glass-card p-6 hover-lift border border-white/50 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-sky-500/10 rounded-full blur-2xl group-hover:bg-sky-500/20 transition-colors duration-500"></div>
            <div class="flex items-center gap-4 mb-4 relative z-10">
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider">{{ __('Court Dates') }}</p>
                    <h3 class="font-heading font-bold text-3xl text-slate-800">{{ $upcomingCourtDates ?? 0 }}</h3>
                </div>
            </div>
            <div class="relative z-10 text-sm text-sky-600 font-medium flex items-center gap-1">
                {{ __('Upcoming proceedings') }} <span class="group-hover:translate-x-1 transition-transform">→</span>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="glass-card p-6 hover-lift border border-white/50 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-colors duration-500"></div>
            <div class="flex items-center gap-4 mb-4 relative z-10">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider">{{ __('Active Clients') }}</p>
                    <h3 class="font-heading font-bold text-3xl text-slate-800">{{ $activeClients ?? 0 }}</h3>
                </div>
            </div>
            <div class="relative z-10 text-sm text-emerald-600 font-medium flex items-center gap-1">
                {{ __('Registered profiles') }} <span class="group-hover:translate-x-1 transition-transform">→</span>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="glass-card p-6 hover-lift border border-white/50 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-colors duration-500"></div>
            <div class="flex items-center gap-4 mb-4 relative z-10">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider">{{ __('Documents') }}</p>
                    <h3 class="font-heading font-bold text-3xl text-slate-800">{{ $totalDocuments ?? 0 }}</h3>
                </div>
            </div>
            <div class="relative z-10 text-sm text-amber-600 font-medium flex items-center gap-1">
                {{ __('Files in secure vault') }} <span class="group-hover:translate-x-1 transition-transform">→</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Recent -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Quick Actions -->
        <div class="lg:col-span-1 space-y-6">
            <div class="glass-card p-6 border border-white/50">
                <h3 class="heading-section !text-xl mb-6">{{ __('Quick Actions') }}</h3>
                <div class="space-y-3">
                    <a href="{{ route('cases.create') }}" class="group flex items-center justify-between p-4 rounded-xl bg-slate-50 hover:bg-indigo-50 border border-slate-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-white shadow-sm flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <span class="font-medium text-slate-700 group-hover:text-indigo-700">{{ __('Open new case') }}</span>
                        </div>
                        <span class="text-slate-400 group-hover:text-indigo-600 group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                    <a href="{{ route('clients.create') }}" class="group flex items-center justify-between p-4 rounded-xl bg-slate-50 hover:bg-sky-50 border border-slate-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-white shadow-sm flex items-center justify-center text-sky-600 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                                </svg>
                            </div>
                            <span class="font-medium text-slate-700 group-hover:text-sky-700">{{ __('Register client') }}</span>
                        </div>
                        <span class="text-slate-400 group-hover:text-sky-600 group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                    <a href="{{ route('documents.create') }}" class="group flex items-center justify-between p-4 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-white shadow-sm flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                </svg>
                            </div>
                            <span class="font-medium text-slate-700 group-hover:text-emerald-700">{{ __('Upload document') }}</span>
                        </div>
                        <span class="text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Workspace Modules -->
        <div class="lg:col-span-2">
            <div class="glass-card p-6 border border-white/50 h-full">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="heading-section !text-xl">{{ __('Workspace Modules') }}</h3>
                    <div class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-semibold uppercase tracking-wider">{{ __('Explore') }}</div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach([
                        ['Cases', route('cases.index'), 'Registry & Status', 'indigo'],
                        ['Clients', route('clients.index'), 'Records & NICs', 'sky'],
                        ['Documents', route('documents.index'), 'File Vault', 'amber'],
                        ['Scheduling', route('court-dates.index'), 'Reminders', 'emerald'],
                    ] as [$label, $url, $desc, $color])
                    
                    <a href="{{ $url }}" class="group block p-5 rounded-2xl border border-slate-200/60 bg-white/40 hover:bg-white hover:border-{{ $color }}-200 hover:shadow-lg hover:shadow-{{ $color }}-500/10 transition-all duration-300">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-heading font-bold text-lg text-slate-800 group-hover:text-{{ $color }}-600 transition-colors">{{ __($label) }}</h4>
                            <div class="w-8 h-8 rounded-full bg-slate-100 group-hover:bg-{{ $color }}-50 flex items-center justify-center text-slate-400 group-hover:text-{{ $color }}-500 transition-colors">
                                <svg class="w-4 h-4 transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500">{{ __($desc) }}</p>
                    </a>
                    
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
