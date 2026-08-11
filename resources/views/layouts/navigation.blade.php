<nav x-data="{ open: false }" class="glass-nav transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-600 to-sky-400 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:shadow-indigo-500/40 transition-all duration-300 group-hover:scale-105">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <span class="font-heading font-bold text-xl tracking-tight text-slate-800">LexLanka</span>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <div class="hidden sm:flex sm:space-x-1">
                    @php
                        $navLinks = [
                            ['route' => 'dashboard', 'label' => 'Dashboard'],
                            ['route' => 'cases.index', 'label' => 'Cases'],
                            ['route' => 'documents.index', 'label' => 'Documents'],
                            ['route' => 'court-dates.index', 'label' => 'Scheduling'],
                            ['route' => 'clients.index', 'label' => 'Clients'],
                        ];
                        if (auth()->user() && auth()->user()->can('view-financials')) {
                            $navLinks[] = ['route' => 'billing.index', 'label' => 'Billing'];
                        }
                        if (auth()->user() && auth()->user()->can('manage-users')) {
                            $navLinks[] = ['route' => 'users.index', 'label' => 'Users'];
                        }
                    @endphp

                    @foreach($navLinks as $link)
                        @php
                            $isActive = request()->routeIs(str_replace('.index', '.*', $link['route'])) || request()->routeIs($link['route']);
                        @endphp
                        <a href="{{ route($link['route']) }}" 
                           class="relative px-3 py-2 text-sm font-medium transition-colors duration-200 group
                                  {{ $isActive ? 'text-indigo-600' : 'text-slate-600 hover:text-slate-900' }}">
                            {{ $link['label'] }}
                            @if($isActive)
                                <span class="absolute inset-x-0 bottom-0 h-0.5 bg-indigo-600 rounded-full"></span>
                            @else
                                <span class="absolute inset-x-0 bottom-0 h-0.5 bg-indigo-600/0 rounded-full transition-all duration-300 group-hover:bg-indigo-600/30"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-xl text-slate-600 bg-white/50 hover:bg-white/80 hover:text-slate-900 focus:outline-none transition ease-in-out duration-200">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
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
                        <x-dropdown-link :href="route('profile.edit')" class="hover:bg-slate-50 hover:text-indigo-600 transition-colors">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="hover:bg-red-50 hover:text-red-600 transition-colors text-slate-600">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100/50 focus:outline-none focus:bg-slate-100/50 transition duration-200">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white/80 backdrop-blur-xl border-b border-slate-200/50 absolute w-full shadow-lg">
        <div class="pt-2 pb-3 space-y-1">
            @foreach($navLinks as $link)
                <x-responsive-nav-link :href="route($link['route'])" :active="request()->routeIs(str_replace('.index', '.*', $link['route']))">
                    {{ $link['label'] }}
                </x-responsive-nav-link>
            @endforeach
        </div>
        <div class="pt-4 pb-1 border-t border-slate-200/50">
            <div class="px-4">
                <div class="font-medium text-base text-slate-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
