<nav x-data="{ open: false, darkMode: document.documentElement.classList.contains('dark') }" class="glass-nav transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="font-sans font-semibold text-2xl tracking-tight text-mist-950 dark:text-white hover:opacity-80 transition-opacity">
                        LexLanka
                    </a>
                </div>

                <div class="hidden sm:flex sm:space-x-1">
                    @php
                        $navLinks = [
                            ['route' => 'dashboard', 'label' => __('Dashboard')],
                            ['route' => 'cases.index', 'label' => __('Cases')],
                            ['route' => 'documents.index', 'label' => __('Documents')],
                            ['route' => 'judgments.index', 'label' => __('Judgment Library')],
                            ['route' => 'court-dates.index', 'label' => __('Scheduling')],
                            ['route' => 'clients.index', 'label' => __('Clients')],
                            ['route' => 'search.index', 'label' => __('Search')],
                        ];
                        if (auth()->user() && auth()->user()->can('view-financials')) {
                            $navLinks[] = ['route' => 'billing.index', 'label' => __('Billing')];
                        }
                        if (auth()->user() && auth()->user()->role === 'partner') {
                            $navLinks[] = ['route' => 'billing.firm-income', 'label' => __('Firm Income')];
                        }
                        if (auth()->user() && auth()->user()->can('manage-users')) {
                            $navLinks[] = ['route' => 'users.index', 'label' => __('Users')];
                        }
                        if (auth()->user() && auth()->user()->can('manage-taxonomy')) {
                            $navLinks[] = ['route' => 'case-categories.index', 'label' => __('Case Categories')];
                            $navLinks[] = ['route' => 'courts.index', 'label' => __('Courts')];
                        }
                    @endphp

                    @foreach($navLinks as $link)
                        @php
                            $isActive = request()->routeIs(str_replace('.index', '.*', $link['route'])) || request()->routeIs($link['route']);
                        @endphp
                        <a href="{{ route($link['route']) }}"
                           class="relative px-3 py-2 text-sm font-medium transition-colors duration-200 group
                                  {{ $isActive ? 'text-mist-950 dark:text-white' : 'text-mist-600 dark:text-mist-300 hover:text-mist-950 dark:hover:text-white' }}">
                            {{ $link['label'] }}
                            @if($isActive)
                                <span class="absolute inset-x-0 bottom-0 h-0.5 bg-mist-950 dark:bg-white rounded-full"></span>
                            @else
                                <span class="absolute inset-x-0 bottom-0 h-0.5 bg-mist-950/0 rounded-full transition-all duration-300 group-hover:bg-mist-950/30 dark:group-hover:bg-white/30"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-2">
                <button
                    type="button"
                    @click="darkMode = !darkMode; if(darkMode) { document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark'); } else { document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light'); }"
                    class="p-2 text-mist-500 hover:text-mist-950 dark:text-mist-400 dark:hover:text-white bg-mist-950/5 dark:bg-white/5 hover:bg-mist-950/10 dark:hover:bg-white/10 rounded-full transition-colors duration-200"
                    title="{{ __('Toggle Dark Mode') }}"
                >
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg x-show="darkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>

                @php
                    $locales = ['en' => 'English', 'si' => 'සිංහල', 'ta' => 'தமிழ்'];
                @endphp
                <x-dropdown align="right" width="w-40">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-full text-mist-600 dark:text-mist-300 bg-mist-950/5 dark:bg-white/5 hover:bg-mist-950/10 dark:hover:bg-white/10 hover:text-mist-950 dark:hover:text-white focus:outline-none transition ease-in-out duration-200">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                            <span>{{ $locales[app()->getLocale()] ?? 'English' }}</span>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @foreach($locales as $code => $label)
                            <form method="POST" action="{{ route('locale.update') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $code }}">
                                <button type="submit"
                                        class="w-full text-start block px-4 py-2 text-sm transition-colors
                                               {{ app()->getLocale() === $code ? 'font-bold text-mist-950 dark:text-white bg-mist-950/10 dark:bg-white/10' : 'text-mist-600 dark:text-mist-300 hover:bg-mist-950/10 dark:hover:bg-white/10 hover:text-mist-950 dark:hover:text-white' }}">
                                    {{ $label }}
                                </button>
                            </form>
                        @endforeach
                    </x-slot>
                </x-dropdown>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-full text-mist-600 dark:text-mist-300 bg-mist-950/5 dark:bg-white/5 hover:bg-mist-950/10 dark:hover:bg-white/10 hover:text-mist-950 dark:hover:text-white focus:outline-none transition ease-in-out duration-200">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-mist-950/10 dark:bg-white/10 text-mist-950 dark:text-white flex items-center justify-center font-bold text-xs">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                                <span>{{ Auth::user()->name }}</span>
                            </div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="hover:bg-red-50 dark:hover:bg-red-500/10 hover:text-red-600 dark:hover:text-red-400 text-mist-600 dark:text-mist-300">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden gap-2">
                <button
                    type="button"
                    @click="darkMode = !darkMode; if(darkMode) { document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark'); } else { document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light'); }"
                    class="inline-flex items-center justify-center p-2 rounded-full text-mist-500 dark:text-mist-400 hover:text-mist-950 dark:hover:text-white hover:bg-mist-950/10 dark:hover:bg-white/10 focus:outline-none transition duration-200"
                >
                    <svg x-show="!darkMode" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg x-show="darkMode" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>

                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-full text-mist-500 dark:text-mist-400 hover:text-mist-950 dark:hover:text-white hover:bg-mist-950/10 dark:hover:bg-white/10 focus:outline-none transition duration-200">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-mist-100 dark:bg-mist-950 border-b border-mist-950/10 dark:border-white/10 absolute w-full shadow-lg">
        <div class="pt-2 pb-3 space-y-1">
            @foreach($navLinks as $link)
                <x-responsive-nav-link :href="route($link['route'])" :active="request()->routeIs(str_replace('.index', '.*', $link['route']))">
                    {{ $link['label'] }}
                </x-responsive-nav-link>
            @endforeach
        </div>
        <div class="pt-4 pb-1 border-t border-mist-950/10 dark:border-white/10">
            <div class="px-4">
                <div class="font-medium text-base text-mist-950 dark:text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-mist-500 dark:text-mist-400">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600 dark:text-red-400">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        <div class="pt-4 pb-3 border-t border-mist-950/10 dark:border-white/10">
            <div class="px-4 mb-2 text-xs font-semibold uppercase tracking-wide text-mist-400 dark:text-mist-500">{{ __('Language') }}</div>
            <div class="px-4 flex items-center gap-2">
                @foreach($locales as $code => $label)
                    <form method="POST" action="{{ route('locale.update') }}">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $code }}">
                        <button type="submit"
                                class="px-3 py-1.5 text-sm rounded-full border transition-colors
                                       {{ app()->getLocale() === $code ? 'font-bold text-mist-950 dark:text-white bg-mist-950/10 dark:bg-white/10 border-mist-950/20 dark:border-white/20' : 'text-mist-600 dark:text-mist-300 border-mist-950/10 dark:border-white/10 hover:bg-mist-950/10 dark:hover:bg-white/10' }}">
                            {{ $label }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</nav>
