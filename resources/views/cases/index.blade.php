<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl">{{ __('Cases Registry') }}</h2>
            <a href="{{ route('cases.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('New Case') }}
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

    @if(session('error'))
        <div class="alert-error mb-6">{{ session('error') }}</div>
    @endif

    <div class="glass-card overflow-hidden">
        <form method="GET" action="{{ route('cases.index') }}" class="p-6 border-b border-mist-950/10 dark:border-white/10 flex items-center justify-between flex-wrap gap-4">
            <div class="relative w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-mist-400 dark:text-mist-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search cases...') }}" class="input-dynamic pl-10 !py-2 !text-sm">
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <select name="main_category_id" onchange="this.form.submit()" class="input-dynamic !py-2 !text-sm !w-auto">
                    <option value="">{{ __('All Case Types') }}</option>
                    @foreach($mainCategories as $mainCategory)
                        <option value="{{ $mainCategory->id }}" @selected(request('main_category_id') == $mainCategory->id)>{{ $mainCategory->name }}</option>
                    @endforeach
                </select>

                <select name="court_id" onchange="this.form.submit()" class="input-dynamic !py-2 !text-sm !w-auto">
                    <option value="">{{ __('All Courts') }}</option>
                    @foreach($courts as $courtOption)
                        <option value="{{ $courtOption->id }}" @selected(request('court_id') == $courtOption->id)>{{ $courtOption->name }}</option>
                    @endforeach
                </select>

                @if(request('search') || request('status') || request('main_category_id') || request('court_id'))
                    <a href="{{ route('cases.index') }}" class="link-subtle underline mr-2">{{ __('Clear') }}</a>
                @endif

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" @click.away="open = false" class="btn-secondary !py-2 !px-4 !text-sm flex items-center gap-2">
                        {{ __('Filter') }}
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-mist-50 dark:bg-mist-900 rounded-xl shadow-lg border border-mist-950/10 dark:border-white/10 z-50 overflow-hidden"
                         style="display: none;">
                        <input type="hidden" name="status" value="{{ request('status') }}" id="status-filter">
                        <div class="p-1">
                            <button type="button" onclick="document.getElementById('status-filter').value=''; this.form.submit()" class="w-full text-left px-4 py-2 text-sm text-mist-700 dark:text-mist-300 hover:bg-mist-950/10 dark:hover:bg-white/10 rounded-lg {{ !request('status') ? 'font-bold text-mist-950 dark:text-white bg-mist-950/10 dark:bg-white/10' : '' }}">
                                {{ __('All Statuses') }}
                            </button>
                            @foreach(\App\Models\LegalCase::STATUSES as $status)
                                <button type="button" onclick="document.getElementById('status-filter').value='{{ $status }}'; this.form.submit()" class="w-full text-left px-4 py-2 text-sm text-mist-700 dark:text-mist-300 hover:bg-mist-950/10 dark:hover:bg-white/10 rounded-lg {{ request('status') === $status ? 'font-bold text-mist-950 dark:text-white bg-mist-950/10 dark:bg-white/10' : '' }}">
                                    {{ __(ucfirst(str_replace('_', ' ', $status))) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <button type="button" class="btn-secondary !py-2 !px-4 !text-sm" onclick="window.print()">{{ __('Export') }}</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="table-dynamic">
                <thead>
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Case Name') }}</th>
                        <th>{{ __('Client') }}</th>
                        <th>{{ __('Case Type') }}</th>
                        <th>{{ __('Court') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                    @forelse($cases as $case)
                        <tr class="table-row-dynamic group">
                            <td>
                                <div class="flex items-center gap-2 font-mono text-xs font-semibold text-mist-700 dark:text-mist-300 bg-mist-950/5 dark:bg-white/5 px-2.5 py-1 rounded-md border border-mist-950/10 dark:border-white/10 w-fit">
                                    LEX-{{ $case->created_at->format('Y') }}-{{ str_pad($case->id, 3, '0', STR_PAD_LEFT) }}
                                </div>
                            </td>
                            <td>
                                <div class="font-medium text-mist-950 dark:text-white">{{ $case->display_name }}</div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-mist-950/10 dark:bg-white/10 text-mist-700 dark:text-mist-300 flex items-center justify-center text-xs font-bold">
                                        {{ substr($case->client->name ?? '?', 0, 1) }}
                                    </div>
                                    <span class="text-mist-700 dark:text-mist-300 font-medium">{{ $case->client->name ?? __('N/A') }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-sm text-mist-700 dark:text-mist-300">{{ $case->caseCategory->name ?? $case->case_type_other ?? '—' }}</div>
                                @if($case->caseCategory?->parent)
                                    <div class="text-xs text-mist-400 dark:text-mist-500">{{ $case->caseCategory->parent->name }}</div>
                                @endif
                            </td>
                            <td class="text-sm text-mist-700 dark:text-mist-300">{{ $case->court->name ?? '—' }}</td>
                            <td>
                                @php
                                    $statusConfig = match($case->status) {
                                        'active' => ['bg-emerald-100 dark:bg-emerald-500/10', 'text-emerald-800 dark:text-emerald-400', 'border-emerald-200 dark:border-emerald-500/20'],
                                        'pending' => ['bg-amber-100 dark:bg-amber-500/10', 'text-amber-800 dark:text-amber-400', 'border-amber-200 dark:border-amber-500/20'],
                                        'trial_scheduled' => ['bg-sky-100 dark:bg-sky-500/10', 'text-sky-800 dark:text-sky-400', 'border-sky-200 dark:border-sky-500/20'],
                                        'judgment_delivered' => ['bg-mist-950/10 dark:bg-white/10', 'text-mist-800 dark:text-mist-200', 'border-mist-950/10 dark:border-white/10'],
                                        default => ['bg-mist-950/5 dark:bg-white/5', 'text-mist-700 dark:text-mist-300', 'border-mist-950/10 dark:border-white/10'],
                                    };
                                @endphp
                                <span class="badge-dynamic {{ $statusConfig[0] }} {{ $statusConfig[1] }} border {{ $statusConfig[2] }}">
                                    {{ __(ucfirst(str_replace('_', ' ', $case->status))) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2 transition-opacity duration-200">
                                    <a href="{{ route('cases.show', $case) }}" class="p-1.5 text-mist-400 dark:text-mist-500 hover:text-mist-950 dark:hover:text-white hover:bg-mist-950/10 dark:hover:bg-white/10 rounded-lg transition-colors" title="{{ __('View') }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('cases.edit', $case) }}" class="p-1.5 text-mist-400 dark:text-mist-500 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-500/10 rounded-lg transition-colors" title="{{ __('Edit') }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('cases.destroy', $case) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this case?') }}');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-mist-400 dark:text-mist-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors" title="{{ __('Delete') }}">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-mist-950/5 dark:bg-white/5 flex items-center justify-center text-mist-400 dark:text-mist-500 mx-auto mb-4">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-mist-950 dark:text-white">{{ __('No cases found') }}</h3>
                                <p class="mt-1 text-mist-500 dark:text-mist-400">{{ __('Get started by opening your first case.') }}</p>
                                <div class="mt-6">
                                    <a href="{{ route('cases.create') }}" class="btn-primary">
                                        {{ __('Open New Case') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($cases) && $cases->hasPages())
            <div class="px-6 py-4 border-t border-mist-950/10 dark:border-white/10">
                {{ $cases->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
