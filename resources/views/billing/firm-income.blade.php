<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="heading-display !text-3xl text-slate-800">{{ __('Firm Income (By Attorney)') }}</h2>
            <p class="text-sm text-slate-500 mt-1">{{ __('Firm-wide appearance income grouped by assigned attorney.') }}</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 animate-fade-in-up stagger-1">
        <div class="glass-card p-6 border border-white/50">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ __('Attorneys With Assigned Cases') }}</div>
            <div class="text-3xl sm:text-4xl font-heading font-bold text-slate-800 font-mono tracking-tight">{{ $attorneySummaries->count() }}</div>
        </div>
        <div class="glass-card p-6 border border-white/50">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ __('Firm-Wide Appearance Income') }}</div>
            <div class="text-3xl sm:text-4xl font-heading font-bold text-emerald-700 font-mono tracking-tight">LKR {{ number_format($grandTotal, 2) }}</div>
        </div>
    </div>

    <div class="space-y-6 animate-fade-in-up stagger-2">
        @forelse($attorneySummaries as $attorneySummary)
            <div
                class="glass-card overflow-hidden"
                x-data="{ open: true }"
            >
                <button
                    type="button"
                    @click="open = !open"
                    class="w-full p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm flex items-center justify-between gap-4 text-left"
                >
                    <div class="min-w-0">
                        <h3 class="heading-section !text-lg truncate">{{ $attorneySummary['attorney']->name }}</h3>
                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                            <span class="badge-dynamic bg-slate-100 text-slate-700 border border-slate-200">
                                {{ __(ucfirst($attorneySummary['attorney']->role)) }}
                            </span>
                            <span class="text-sm text-slate-500">
                                {{ __('Rate: LKR :rate / trial date', ['rate' => number_format($attorneySummary['rate'], 2)]) }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 shrink-0">
                        <div class="text-right hidden sm:block">
                            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Subtotal') }}</div>
                            <div class="text-xl font-heading font-bold text-emerald-700 font-mono tracking-tight">
                                LKR {{ number_format($attorneySummary['total_income'], 2) }}
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                >
                    <div class="sm:hidden px-6 py-3 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Subtotal') }}</span>
                        <span class="font-mono font-bold text-emerald-700">LKR {{ number_format($attorneySummary['total_income'], 2) }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-dynamic">
                            <thead>
                                <tr>
                                    <th>{{ __('Case / Client') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Trial Dates') }}</th>
                                    <th class="text-right">{{ __('Appearance Fee') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($attorneySummary['case_summaries'] as $summary)
                                    <tr class="table-row-dynamic group">
                                        <td>
                                            <div class="font-medium text-slate-800">{{ $summary['case']->client->name ?? __('Unknown Client') }}</div>
                                            <div class="text-xs text-slate-500 font-mono mt-0.5">LEX-{{ $summary['case']->created_at->format('Y').'-'.str_pad($summary['case']->id, 3, '0', STR_PAD_LEFT) }}</div>
                                        </td>
                                        <td>
                                            <span class="badge-dynamic bg-slate-100 text-slate-600 border border-slate-200">
                                                {{ __(str_replace('_', ' ', ucfirst($summary['case']->status))) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-dynamic bg-slate-100 text-slate-600 border border-slate-200">{{ __(':count dates', ['count' => $summary['trial_date_count']]) }}</span>
                                        </td>
                                        <td class="text-right font-mono font-medium text-slate-800">
                                            LKR {{ number_format($summary['appearance_fee'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-card p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-900">{{ __('No attorney income yet') }}</h3>
                <p class="mt-1 text-slate-500">{{ __("Once cases are assigned, each attorney's appearance income will appear here.") }}</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
