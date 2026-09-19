<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl">{{ __('Court Schedule') }}</h2>
            <a href="{{ route('court-dates.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ __('Schedule Appearance') }}
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert-success mb-6">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

        <!-- List View -->
        <div class="lg:col-span-3">
            <div class="glass-card overflow-hidden">
                <div class="p-6 border-b border-mist-950/10 dark:border-white/10 flex items-center justify-between">
                    <h3 class="heading-section !text-lg">{{ __('Upcoming Dates') }}</h3>
                    <div class="flex items-center gap-2">
                        <select class="input-dynamic !py-1.5 !text-sm !w-auto">
                            <option>{{ __('All Types') }}</option>
                            <option>{{ __('Trial') }}</option>
                            <option>{{ __('Hearing') }}</option>
                            <option>{{ __('Motion') }}</option>
                        </select>
                    </div>
                </div>

                <div class="p-0">
                    <div class="divide-y divide-mist-950/10 dark:divide-white/10">
                        @php
                            $courtDates = collect();
                            if(isset($upcomingByMonth)) {
                                foreach($upcomingByMonth as $dates) { $courtDates = $courtDates->merge($dates); }
                            }
                            if(isset($pastByMonth)) {
                                foreach($pastByMonth as $dates) { $courtDates = $courtDates->merge($dates); }
                            }
                        @endphp
                        @forelse($courtDates as $date)
                            <div class="p-6 flex flex-col sm:flex-row gap-6 hover:bg-mist-950/5 dark:hover:bg-white/5 transition-colors group">
                                <!-- Date Block -->
                                <div class="flex-shrink-0 text-center w-20">
                                    <div class="text-xs font-bold uppercase tracking-widest text-mist-600 dark:text-mist-400 mb-1">
                                        {{ \Carbon\Carbon::parse($date->date)->format('M') }}
                                    </div>
                                    <div class="text-4xl font-sans font-semibold text-mist-950 dark:text-white leading-none">
                                        {{ \Carbon\Carbon::parse($date->date)->format('d') }}
                                    </div>
                                    <div class="text-xs text-mist-400 dark:text-mist-500 mt-1 font-medium">
                                        {{ \Carbon\Carbon::parse($date->date)->format('Y') }}
                                    </div>
                                </div>

                                <!-- Details Block -->
                                <div class="flex-grow">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="text-lg font-bold text-mist-950 dark:text-white group-hover:text-mist-700 dark:group-hover:text-mist-200 transition-colors">
                                                {{ $date->title }}
                                            </h4>
                                            <div class="flex items-center gap-4 mt-2 text-sm text-mist-500 dark:text-mist-400">
                                                <span class="flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-mist-400 dark:text-mist-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ \Carbon\Carbon::parse($date->date)->format('g:i A') }}
                                                </span>
                                                <span class="flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-mist-400 dark:text-mist-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    {{ $date->location ?? __('Court Room') }}
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            @php
                                                $typeColors = match($date->type) {
                                                    'trial' => 'bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400 border-red-200 dark:border-red-500/20',
                                                    'hearing' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-800 dark:text-amber-400 border-amber-200 dark:border-amber-500/20',
                                                    'motion' => 'bg-mist-950/10 dark:bg-white/10 text-mist-800 dark:text-mist-200 border-mist-950/10 dark:border-white/10',
                                                    default => 'bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300 border-mist-950/10 dark:border-white/10',
                                                };
                                            @endphp
                                            <span class="badge-dynamic border {{ $typeColors }}">
                                                {{ ucfirst($date->type) }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($date->legalCase)
                                    <div class="mt-4 p-3 rounded-xl bg-mist-950/5 dark:bg-white/5 border border-mist-950/10 dark:border-white/10 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded bg-white dark:bg-mist-900 shadow-sm flex items-center justify-center text-mist-400 dark:text-mist-500">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-mist-950 dark:text-white">{{ $date->legalCase->display_name }}</div>
                                                <div class="text-xs text-mist-700 dark:text-mist-300 font-mono mt-0.5">LEX-{{ $date->legalCase->created_at->format('Y') }}-{{ str_pad($date->legalCase->id, 3, '0', STR_PAD_LEFT) }}</div>
                                            </div>
                                        </div>
                                        <a href="{{ route('cases.show', $date->legalCase) }}" class="link-arrow !text-xs">{{ __('View Case') }} &rarr;</a>
                                    </div>
                                    @endif

                                    @if($date->notes)
                                        <p class="mt-3 text-sm text-mist-500 dark:text-mist-400 italic">"{{ $date->notes }}"</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-12 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-mist-950/5 dark:bg-white/5 flex items-center justify-center text-mist-400 dark:text-mist-500 mx-auto mb-4">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-mist-950 dark:text-white">{{ __('No scheduled dates') }}</h3>
                                <p class="mt-1 text-mist-500 dark:text-mist-400">{{ __('Your calendar is clear.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Mini Calendar Widget -->
        <div class="lg:col-span-1">
            <div
                class="glass-card p-6 sticky top-24 z-20"
                x-data="{
                    year: new Date().getFullYear(),
                    month: new Date().getMonth(),
                    events: @js($calendarEvents),
                    tipDay: null,
                    tipLabel: '',
                    tipEvents: [],

                    get monthLabel() {
                        return new Date(this.year, this.month, 1)
                            .toLocaleDateString('{{ app()->getLocale() }}', { month: 'long', year: 'numeric' });
                    },

                    get todayStr() {
                        const t = new Date();
                        return this.fmt(t.getFullYear(), t.getMonth() + 1, t.getDate());
                    },

                    get isCurrentMonth() {
                        const t = new Date();
                        return this.year === t.getFullYear() && this.month === t.getMonth();
                    },

                    fmt(y, m, d) {
                        return y + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0');
                    },

                    get calendarDays() {
                        const days = [];
                        const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                        let startDay = new Date(this.year, this.month, 1).getDay();
                        startDay = startDay === 0 ? 6 : startDay - 1;

                        const prevDays = new Date(this.year, this.month, 0).getDate();
                        for (let i = startDay - 1; i >= 0; i--) {
                            const d = prevDays - i;
                            let m = this.month;
                            let y = this.year;
                            if (m === 0) { m = 12; y--; } 
                            days.push({ day: d, inMonth: false, date: this.fmt(y, m, d) });
                        }

                        for (let d = 1; d <= daysInMonth; d++) {
                            days.push({ day: d, inMonth: true, date: this.fmt(this.year, this.month + 1, d) });
                        }

                        const rem = 7 - (days.length % 7);
                        if (rem < 7) {
                            let nm = this.month + 2;
                            let ny = this.year;
                            if (nm > 12) { nm = 1; ny++; }
                            for (let d = 1; d <= rem; d++) {
                                days.push({ day: d, inMonth: false, date: this.fmt(ny, nm, d) });
                            }
                        }
                        return days;
                    },

                    hasEvents(dateStr) {
                        return this.events[dateStr] && this.events[dateStr].length > 0;
                    },

                    prevMonth() {
                        this.month--;
                        if (this.month < 0) { this.month = 11; this.year--; }
                        this.tipDay = null;
                    },

                    nextMonth() {
                        this.month++;
                        if (this.month > 11) { this.month = 0; this.year++; }
                        this.tipDay = null;
                    },

                    goToday() {
                        const t = new Date();
                        this.year = t.getFullYear();
                        this.month = t.getMonth();
                        this.tipDay = null;
                    },

                    showTip(dateStr) {
                        if (!this.hasEvents(dateStr)) return;
                        const d = new Date(dateStr + 'T00:00:00');
                        this.tipDay = dateStr;
                        this.tipLabel = d.toLocaleDateString('{{ app()->getLocale() }}', { weekday: 'short', month: 'short', day: 'numeric' });
                        this.tipEvents = this.events[dateStr];
                    },

                    hideTip(dateStr) {
                        if (this.tipDay === dateStr) {
                            this.tipDay = null;
                            this.tipLabel = '';
                            this.tipEvents = [];
                        }
                    }
                }"
            >
                {{-- Month Header with Navigation --}}
                <div class="flex items-center justify-between mb-4">
                    <button
                        @click="prevMonth()"
                        type="button"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-mist-400 dark:text-mist-500 hover:text-mist-700 dark:hover:text-mist-300 hover:bg-mist-950/10 dark:hover:bg-white/10 transition-colors"
                        title="{{ __('Previous month') }}"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <h3 class="heading-section !text-lg !mb-0 select-none cursor-pointer" @click="goToday()" title="{{ __('Go to today') }}" x-text="monthLabel"></h3>

                    <button
                        @click="nextMonth()"
                        type="button"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-mist-400 dark:text-mist-500 hover:text-mist-700 dark:hover:text-mist-300 hover:bg-mist-950/10 dark:hover:bg-white/10 transition-colors"
                        title="{{ __('Next month') }}"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                {{-- "Today" pill — only visible when viewing a different month --}}
                <div class="flex justify-center mb-3" x-show="!isCurrentMonth" x-cloak>
                    <button
                        @click="goToday()"
                        type="button"
                        class="text-xs font-semibold text-mist-700 dark:text-mist-300 bg-mist-950/5 dark:bg-white/5 hover:bg-mist-950/10 dark:hover:bg-white/10 px-3 py-1 rounded-full transition-colors"
                    >
                        ↩ {{ __('Today') }}
                    </button>
                </div>

                {{-- Day-of-week headers --}}
                <div class="grid grid-cols-7 gap-1 text-center mb-2">
                    @foreach(['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $day)
                        <div class="text-xs font-semibold text-mist-400 dark:text-mist-500 py-1">{{ __($day) }}</div>
                    @endforeach
                </div>

                {{-- Calendar grid (Alpine-rendered) --}}
                <div class="grid grid-cols-7 gap-1 text-center">
                    <template x-for="(cell, idx) in calendarDays" :key="idx">
                        <div
                            class="relative py-1.5 flex justify-center"
                            x-bind:tabindex="hasEvents(cell.date) ? 0 : -1"
                            x-bind:data-calendar-day="cell.date"
                            @mouseenter="showTip(cell.date)"
                            @mouseleave="hideTip(cell.date)"
                            @focus="showTip(cell.date)"
                            @blur="hideTip(cell.date)"
                        >
                            <span
                                class="w-7 h-7 flex items-center justify-center rounded-full text-sm transition-colors"
                                :class="{
                                    'text-mist-300 dark:text-mist-700': !cell.inMonth,
                                    'text-mist-700 dark:text-mist-300': cell.inMonth && cell.date !== todayStr && !hasEvents(cell.date),
                                    'bg-mist-950 dark:bg-mist-300 text-white dark:text-mist-950 font-bold shadow-md shadow-mist-950/20': cell.date === todayStr,
                                    'font-semibold text-mist-800 dark:text-mist-200 hover:bg-mist-950/10 dark:hover:bg-white/10 cursor-help': hasEvents(cell.date) && cell.date !== todayStr,
                                    'hover:bg-mist-950/5 dark:hover:bg-white/5': !hasEvents(cell.date) && cell.date !== todayStr,
                                    'bg-mist-950/10 dark:bg-white/10 ring-2 ring-mist-950/20 dark:ring-white/20': tipDay === cell.date && cell.date !== todayStr,
                                }"
                                x-text="cell.day"
                            ></span>
                            <span
                                x-show="hasEvents(cell.date)"
                                class="absolute bottom-0.5 w-1 h-1 rounded-full"
                                :class="cell.date === todayStr ? 'bg-white' : 'bg-sky-500'"
                            ></span>
                        </div>
                    </template>
                </div>

                {{-- Tooltip panel --}}
                <div
                    x-show="tipDay !== null"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="mt-4"
                    data-calendar-tooltip-panel
                >
                    <div class="rounded-xl bg-mist-950 text-white shadow-lg ring-1 ring-white/10 p-3 text-left">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-mist-400 mb-2" x-text="tipLabel"></div>
                        <ul class="space-y-2.5">
                            <template x-for="(event, index) in tipEvents" :key="index">
                                <li class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[11px] font-medium text-mist-300 tabular-nums" x-text="event.time"></span>
                                        <span
                                            class="text-[10px] font-semibold px-1.5 py-0.5 rounded"
                                            :class="event.is_trial ? 'bg-red-500/20 text-red-200' : 'bg-sky-500/20 text-sky-200'"
                                            x-text="event.type"
                                        ></span>
                                    </div>
                                    <div class="text-xs text-white/90 truncate mt-0.5" x-text="event.case" :title="event.case"></div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
