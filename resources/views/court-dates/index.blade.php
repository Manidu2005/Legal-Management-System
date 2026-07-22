<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Court Dates') }}
            </h2>
            <a href="{{ route('court-dates.create') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('Add Court Date') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Success Message --}}
            @if (session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center">
                    <svg class="w-5 h-5 me-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Mini Calendar --}}
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 me-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                {{ $currentMonth->format('F Y') }}
                            </h3>

                            {{-- Calendar Grid --}}
                            <div class="grid grid-cols-7 gap-1 text-center">
                                {{-- Day headers --}}
                                @foreach (['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $dayName)
                                    <div class="text-xs font-semibold text-gray-500 py-1">{{ $dayName }}</div>
                                @endforeach

                                {{-- Empty cells for offset --}}
                                @for ($i = 0; $i < $firstDayOfWeek; $i++)
                                    <div class="py-1"></div>
                                @endfor

                                {{-- Day cells --}}
                                @for ($day = 1; $day <= $daysInMonth; $day++)
                                    @php
                                        $dayStr = (string) $day;
                                        $hasEvents = isset($calendarDates[$dayStr]);
                                        $isToday = $day === (int) now()->format('j') && $currentMonth->format('m Y') === now()->format('m Y');
                                        $dayEvents = $hasEvents ? $calendarDates[$dayStr] : collect();
                                        $hasCallingDate = $dayEvents->contains(fn ($e) => $e->type === 'calling_date');
                                        $hasTrialDate = $dayEvents->contains(fn ($e) => $e->type === 'trial_date');
                                    @endphp
                                    <div class="relative py-1 rounded-md text-sm
                                        {{ $isToday ? 'bg-indigo-600 text-white font-bold' : '' }}
                                        {{ $hasEvents && !$isToday ? 'bg-gray-100 font-medium' : '' }}
                                        {{ !$hasEvents && !$isToday ? 'text-gray-700' : '' }}">
                                        {{ $day }}
                                        @if ($hasEvents)
                                            <div class="flex justify-center gap-0.5 mt-0.5">
                                                @if ($hasCallingDate)
                                                    <span class="block w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                @endif
                                                @if ($hasTrialDate)
                                                    <span class="block w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endfor
                            </div>

                            {{-- Calendar Legend --}}
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex items-center gap-4 text-xs text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <span class="block w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                        Calling Date
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="block w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                        Trial Date
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Court Dates List --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Upcoming Court Dates --}}
                    @if ($upcomingByMonth->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 me-2 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    {{ __('Upcoming Court Dates') }}
                                </h3>

                                @foreach ($upcomingByMonth as $month => $dates)
                                    <div class="mb-6 last:mb-0">
                                        <h4 class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-3 flex items-center">
                                            <span class="bg-indigo-50 px-3 py-1 rounded-full">{{ $month }}</span>
                                        </h4>
                                        <div class="space-y-3">
                                            @foreach ($dates as $courtDate)
                                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors">
                                                    <div class="flex items-start gap-4">
                                                        {{-- Date Block --}}
                                                        <div class="flex-shrink-0 text-center bg-white rounded-lg shadow-sm border border-gray-200 px-3 py-2 min-w-[4rem]">
                                                            <div class="text-xs font-semibold text-gray-500 uppercase">{{ $courtDate->date->format('M') }}</div>
                                                            <div class="text-2xl font-bold text-gray-900">{{ $courtDate->date->format('d') }}</div>
                                                            <div class="text-xs text-gray-500">{{ $courtDate->date->format('D') }}</div>
                                                        </div>

                                                        {{-- Details --}}
                                                        <div>
                                                            {{-- Type Badge --}}
                                                            @if ($courtDate->type === 'trial_date')
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                    <svg class="w-3 h-3 me-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                                                    Trial Date
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                    <svg class="w-3 h-3 me-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                                                    Calling Date
                                                                </span>
                                                            @endif

                                                            {{-- Case Reference --}}
                                                            <div class="mt-1">
                                                                <span class="text-sm font-semibold text-gray-900">
                                                                    Case #{{ $courtDate->legalCase->id }}
                                                                </span>
                                                                @if ($courtDate->legalCase->client)
                                                                    <span class="text-sm text-gray-500">
                                                                        — {{ $courtDate->legalCase->client->name }}
                                                                    </span>
                                                                @endif
                                                            </div>

                                                            {{-- Time & Attorney --}}
                                                            <div class="mt-1 flex items-center gap-3 text-xs text-gray-500">
                                                                <span class="flex items-center gap-1">
                                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                                    </svg>
                                                                    {{ $courtDate->date->format('g:i A') }}
                                                                </span>
                                                                @if ($courtDate->legalCase->assignedAttorney)
                                                                    <span class="flex items-center gap-1">
                                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                                        </svg>
                                                                        {{ $courtDate->legalCase->assignedAttorney->name }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Actions --}}
                                                    <div class="flex items-center gap-3">
                                                        {{-- Reminder Status --}}
                                                        @if ($courtDate->type === 'trial_date')
                                                            @if ($courtDate->reminder_sent)
                                                                <span class="flex items-center text-xs text-green-600" title="Reminder sent">
                                                                    <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                                    </svg>
                                                                    Sent
                                                                </span>
                                                            @else
                                                                <span class="flex items-center text-xs text-amber-600" title="Reminder pending">
                                                                    <svg class="w-4 h-4 me-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                                    </svg>
                                                                    Pending
                                                                </span>
                                                            @endif
                                                        @endif

                                                        {{-- Delete Button --}}
                                                        <form action="{{ route('court-dates.destroy', $courtDate) }}" method="POST"
                                                              onsubmit="return confirm('Are you sure you want to delete this court date?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Past Court Dates --}}
                    @if ($pastByMonth->isNotEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 me-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                    </svg>
                                    {{ __('Past Court Dates') }}
                                </h3>

                                @foreach ($pastByMonth as $month => $dates)
                                    <div class="mb-6 last:mb-0">
                                        <h4 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">
                                            <span class="bg-gray-50 px-3 py-1 rounded-full">{{ $month }}</span>
                                        </h4>
                                        <div class="space-y-3">
                                            @foreach ($dates as $courtDate)
                                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100 opacity-75">
                                                    <div class="flex items-start gap-4">
                                                        {{-- Date Block --}}
                                                        <div class="flex-shrink-0 text-center bg-white rounded-lg shadow-sm border border-gray-200 px-3 py-2 min-w-[4rem]">
                                                            <div class="text-xs font-semibold text-gray-400 uppercase">{{ $courtDate->date->format('M') }}</div>
                                                            <div class="text-2xl font-bold text-gray-400">{{ $courtDate->date->format('d') }}</div>
                                                            <div class="text-xs text-gray-400">{{ $courtDate->date->format('D') }}</div>
                                                        </div>

                                                        {{-- Details --}}
                                                        <div>
                                                            @if ($courtDate->type === 'trial_date')
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">
                                                                    Trial Date
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-600">
                                                                    Calling Date
                                                                </span>
                                                            @endif

                                                            <div class="mt-1">
                                                                <span class="text-sm font-semibold text-gray-600">
                                                                    Case #{{ $courtDate->legalCase->id }}
                                                                </span>
                                                                @if ($courtDate->legalCase->client)
                                                                    <span class="text-sm text-gray-400">
                                                                        — {{ $courtDate->legalCase->client->name }}
                                                                    </span>
                                                                @endif
                                                            </div>

                                                            <div class="mt-1 flex items-center gap-3 text-xs text-gray-400">
                                                                <span>{{ $courtDate->date->format('g:i A') }}</span>
                                                                @if ($courtDate->legalCase->assignedAttorney)
                                                                    <span>{{ $courtDate->legalCase->assignedAttorney->name }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Delete --}}
                                                    <form action="{{ route('court-dates.destroy', $courtDate) }}" method="POST"
                                                          onsubmit="return confirm('Are you sure you want to delete this court date?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Empty State --}}
                    @if ($upcomingByMonth->isEmpty() && $pastByMonth->isEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">No court dates</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by scheduling a new court date.</p>
                                <div class="mt-6">
                                    <a href="{{ route('court-dates.create') }}"
                                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        {{ __('Add Court Date') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
