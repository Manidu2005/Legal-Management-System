<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl text-slate-800">{{ __('Court Schedule') }}</h2>
            <a href="{{ route('court-dates.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ __('Schedule Appearance') }}
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="glass border border-emerald-500/30 bg-emerald-50/80 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3 animate-fade-in-up">
            <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- List View -->
        <div class="lg:col-span-3">
            <div class="glass-card overflow-hidden">
                <div class="p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm flex items-center justify-between">
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
                    <div class="divide-y divide-slate-100">
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
                            <div class="p-6 flex flex-col sm:flex-row gap-6 hover:bg-slate-50/50 transition-colors group">
                                <!-- Date Block -->
                                <div class="flex-shrink-0 text-center w-20">
                                    <div class="text-xs font-bold uppercase tracking-widest text-indigo-500 mb-1">
                                        {{ \Carbon\Carbon::parse($date->date)->format('M') }}
                                    </div>
                                    <div class="text-4xl font-heading font-bold text-slate-800 leading-none">
                                        {{ \Carbon\Carbon::parse($date->date)->format('d') }}
                                    </div>
                                    <div class="text-xs text-slate-400 mt-1 font-medium">
                                        {{ \Carbon\Carbon::parse($date->date)->format('Y') }}
                                    </div>
                                </div>
                                
                                <!-- Details Block -->
                                <div class="flex-grow">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="text-lg font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                                {{ $date->title }}
                                            </h4>
                                            <div class="flex items-center gap-4 mt-2 text-sm text-slate-500">
                                                <span class="flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ \Carbon\Carbon::parse($date->date)->format('g:i A') }}
                                                </span>
                                                <span class="flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                                    'trial' => 'bg-red-100 text-red-700 border-red-200',
                                                    'hearing' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                    'motion' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                                                };
                                            @endphp
                                            <span class="badge-dynamic border {{ $typeColors }}">
                                                {{ ucfirst($date->type) }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($date->legalCase)
                                    <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded bg-white shadow-sm flex items-center justify-center text-slate-400">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-slate-800">{{ $date->legalCase->display_name }}</div>
                                                <div class="text-xs text-indigo-600 font-mono mt-0.5">LEX-{{ $date->legalCase->created_at->format('Y') }}-{{ str_pad($date->legalCase->id, 3, '0', STR_PAD_LEFT) }}</div>
                                            </div>
                                        </div>
                                        <a href="{{ route('cases.show', $date->legalCase) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">{{ __('View Case') }} &rarr;</a>
                                    </div>
                                    @endif
                                    
                                    @if($date->notes)
                                        <p class="mt-3 text-sm text-slate-500 italic">"{{ $date->notes }}"</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-12 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-4">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-slate-900">{{ __('No scheduled dates') }}</h3>
                                <p class="mt-1 text-slate-500">{{ __('Your calendar is clear.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            

        </div>

        <!-- Mini Calendar Widget -->
        <div class="lg:col-span-1">
            <div
                class="glass-card p-6 border border-white/50 sticky top-24 z-20"
                x-data="{
                    tipDay: null,
                    tipLabel: '',
                    tipEvents: [],
                    show(day, label, events) {
                        this.tipDay = day;
                        this.tipLabel = label;
                        this.tipEvents = events;
                    },
                    hide(day) {
                        if (this.tipDay === day) {
                            this.tipDay = null;
                            this.tipLabel = '';
                            this.tipEvents = [];
                        }
                    }
                }"
            >
                <h3 class="heading-section !text-lg mb-4">{{ now()->translatedFormat('F Y') }}</h3>
                <div class="grid grid-cols-7 gap-1 text-center mb-2">
                    @foreach(['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'] as $day)
                        <div class="text-xs font-semibold text-slate-400 py-1">{{ __($day) }}</div>
                    @endforeach
                </div>
                <div class="grid grid-cols-7 gap-1 text-center">
                    @php
                        $start = now()->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::MONDAY);
                        $end = now()->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
                        $current = $start->copy();
                        $calendarDates = $calendarDates ?? collect();
                    @endphp
                    @while($current <= $end)
                        @php
                            $dayKey = $current->toDateString();
                            $dayEvents = collect($calendarDates->get($dayKey, []));
                            $hasEvent = $dayEvents->isNotEmpty();
                            $isCurrentMonth = $current->month == now()->month;
                            $isToday = $current->isToday();
                            $dayLabel = $current->translatedFormat('D, M j');
                            $tipPayload = $dayEvents->map(fn ($event) => [
                                'time' => $event->date->format('g:i A'),
                                'type' => $event->type === 'trial_date' ? __('Trial Date') : __('Calling Date'),
                                'is_trial' => $event->type === 'trial_date',
                                'case' => $event->legalCase?->display_name ?? __('Unknown Client'),
                            ])->values()->all();
                        @endphp
                        <div
                            class="relative py-1.5 flex justify-center"
                            @if($hasEvent)
                                data-calendar-day="{{ $dayKey }}"
                                tabindex="0"
                                @mouseenter='show(@json($dayKey), @json($dayLabel), @json($tipPayload))'
                                @mouseleave='hide(@json($dayKey))'
                                @focus='show(@json($dayKey), @json($dayLabel), @json($tipPayload))'
                                @blur='hide(@json($dayKey))'
                            @endif
                        >
                            <span
                                class="w-7 h-7 flex items-center justify-center rounded-full text-sm transition-colors
                                    {{ !$isCurrentMonth ? 'text-slate-300' : 'text-slate-700' }}
                                    {{ $isToday ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-500/30' : '' }}
                                    {{ $hasEvent && !$isToday ? 'font-semibold text-indigo-700 hover:bg-indigo-50 cursor-help' : '' }}
                                    {{ !$hasEvent && !$isToday ? 'hover:bg-slate-100' : '' }}"
                                :class="tipDay === @js($dayKey) && !@js($isToday) ? 'bg-indigo-50 ring-2 ring-indigo-200' : ''"
                            >
                                {{ $current->day }}
                            </span>
                            @if($hasEvent)
                                <span class="absolute bottom-0.5 w-1 h-1 rounded-full {{ $isToday ? 'bg-white' : 'bg-sky-500' }}"></span>
                            @endif
                        </div>
                        @php $current->addDay(); @endphp
                    @endwhile
                </div>

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
                    <div class="rounded-xl bg-slate-900 text-white shadow-lg ring-1 ring-white/10 p-3 text-left">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2" x-text="tipLabel"></div>
                        <ul class="space-y-2.5">
                            <template x-for="(event, index) in tipEvents" :key="index">
                                <li class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[11px] font-medium text-slate-300 tabular-nums" x-text="event.time"></span>
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
