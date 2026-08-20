<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('billing.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors shrink-0" title="{{ __('Back to Billing') }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div class="min-w-0">
                    <h2 class="heading-display !text-2xl sm:!text-3xl text-slate-800 truncate">{{ __('Case Billing') }}</h2>
                    <p class="text-sm text-slate-500 truncate mt-0.5">{{ $case->display_name }}</p>
                </div>
            </div>
            <a href="{{ route('billing.report', $case->id) }}" class="btn-secondary !py-2 !px-4 !text-sm shrink-0">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                {{ __('Generate Client Report') }}
            </a>
        </div>
    </x-slot>

    @if (session('success'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="glass border border-emerald-500/30 bg-emerald-50/80 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex items-center justify-between gap-3"
        >
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-emerald-600/70 hover:text-emerald-800 transition-colors" aria-label="{{ __('Dismiss') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Case Information --}}
    <div class="glass-card overflow-hidden mb-6 animate-fade-in-up stagger-1">
        <div class="p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm">
            <h3 class="heading-section !text-lg">{{ __('Case Information') }}</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ __('Client') }}</p>
                    <p class="text-base font-medium text-slate-800">{{ $case->client->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ __('Assigned Attorney') }}</p>
                    <p class="text-base font-medium text-slate-800">{{ $case->assignedAttorney->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ __('Attorney Rate') }}</p>
                    <p class="text-base font-medium text-slate-800 font-mono">LKR {{ number_format($attorneyRate, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ __('Case Type') }}</p>
                    <p class="text-base font-medium text-slate-800">
                        @if ($case->case_type)
                            {{ __(ucfirst(str_replace('_', ' ', $case->case_type))) }}
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ __('Status') }}</p>
                    <span class="badge-dynamic bg-slate-100 text-slate-700 border border-slate-200">
                        {{ __(str_replace('_', ' ', ucfirst($case->status))) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Appearance Fee + Balances --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 animate-fade-in-up stagger-2">
        <div class="glass-card p-6 border border-white/50 lg:col-span-1 bg-slate-800 text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-800 via-slate-800 to-indigo-900/80 pointer-events-none"></div>
            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">{{ __('Appearance Fee Calculation') }}</p>
                <p class="text-sm text-slate-300 mb-3">
                    {{ __(':count trial dates', ['count' => $trialDateCount]) }}
                    &times; LKR {{ number_format($attorneyRate, 2) }}
                </p>
                <p class="text-3xl font-heading font-bold font-mono tracking-tight">
                    LKR {{ number_format($summary['appearance_fee'], 2) }}
                </p>
            </div>
        </div>

        <div class="glass-card p-6 border border-white/50">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 rounded-xl bg-slate-100 p-3 text-slate-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ __('Trust Balance') }}</p>
                    <p class="text-2xl font-heading font-bold font-mono tracking-tight {{ $summary['balances']['trust'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        LKR {{ number_format($summary['balances']['trust'], 2) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="glass-card p-6 border border-white/50">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 rounded-xl bg-slate-100 p-3 text-slate-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ __('Operational Balance') }}</p>
                    <p class="text-2xl font-heading font-bold font-mono tracking-tight {{ $summary['balances']['operational'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        LKR {{ number_format($summary['balances']['operational'], 2) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Ledger Entry --}}
    <div class="glass-card overflow-hidden mb-6 animate-fade-in-up stagger-3" x-data="{ open: true }">
        <button
            type="button"
            @click="open = !open"
            class="w-full p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm flex items-center justify-between text-left"
        >
            <h3 class="heading-section !text-lg">{{ __('Add Ledger Entry') }}</h3>
            <svg class="w-5 h-5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="p-6"
        >
            <form method="POST" action="{{ route('ledger-entries.store') }}">
                @csrf
                <input type="hidden" name="case_id" value="{{ $case->id }}">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="label-dynamic">{{ __('Ledger Type') }}</label>
                        <div class="flex items-center gap-5 mt-1">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="type" value="trust" class="rounded-full border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500/40" {{ old('type', 'trust') === 'trust' ? 'checked' : '' }}>
                                <span class="ms-2 text-sm text-slate-700">{{ __('Trust') }}</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="type" value="operational" class="rounded-full border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500/40" {{ old('type') === 'operational' ? 'checked' : '' }}>
                                <span class="ms-2 text-sm text-slate-700">{{ __('Operational') }}</span>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('type')" class="mt-1" />
                    </div>

                    <div>
                        <label for="amount" class="label-dynamic">{{ __('Amount (LKR)') }}</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" placeholder="0.00" required class="input-dynamic !py-2.5" />
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>

                    <div>
                        <label for="description" class="label-dynamic">{{ __('Description') }}</label>
                        <input id="description" name="description" type="text" value="{{ old('description') }}" placeholder="{{ __('e.g. Court filing fee') }}" required maxlength="500" class="input-dynamic !py-2.5" />
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div>
                        <button type="submit" class="btn-primary w-full !py-2.5 !text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ __('Add Entry') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Ledgers --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-fade-in-up stagger-4">
        {{-- Trust Ledger --}}
        <div class="glass-card overflow-hidden">
            <div class="p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm flex items-center justify-between gap-3">
                <h3 class="heading-section !text-lg">{{ __('Trust Ledger') }}</h3>
                <span class="badge-dynamic border {{ $summary['balances']['trust'] >= 0 ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-red-50 text-red-700 border-red-200' }} font-mono">
                    LKR {{ number_format($summary['balances']['trust'], 2) }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="table-dynamic">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-right">{{ __('Amount') }}</th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($trustEntries as $entry)
                            <tr class="table-row-dynamic group">
                                <td class="whitespace-nowrap text-slate-500">
                                    {{ $entry->created_at->format('d M Y') }}
                                </td>
                                <td class="text-slate-700 !whitespace-normal">
                                    {{ $entry->description }}
                                </td>
                                <td class="text-right font-mono font-medium text-emerald-600">
                                    LKR {{ number_format($entry->amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('ledger-entries.destroy', $entry->id) }}" class="inline" onsubmit="return confirm('{{ __('Delete this ledger entry?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-300 group-hover:text-red-500 hover:!text-red-600 transition-colors duration-150" title="{{ __('Delete') }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="!px-6 !py-12 text-center">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-700">{{ __('No trust ledger entries yet.') }}</p>
                                    <p class="text-xs text-slate-500 mt-1">{{ __('Client retainers and trust deposits will appear here.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Operational Ledger --}}
        <div class="glass-card overflow-hidden">
            <div class="p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm flex items-center justify-between gap-3">
                <h3 class="heading-section !text-lg">{{ __('Operational Ledger') }}</h3>
                <span class="badge-dynamic border {{ $summary['balances']['operational'] >= 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200' }} font-mono">
                    LKR {{ number_format($summary['balances']['operational'], 2) }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="table-dynamic">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-right">{{ __('Amount') }}</th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($operationalEntries as $entry)
                            <tr class="table-row-dynamic group">
                                <td class="whitespace-nowrap text-slate-500">
                                    {{ $entry->created_at->format('d M Y') }}
                                </td>
                                <td class="text-slate-700 !whitespace-normal">
                                    {{ $entry->description }}
                                </td>
                                <td class="text-right font-mono font-medium text-emerald-600">
                                    LKR {{ number_format($entry->amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('ledger-entries.destroy', $entry->id) }}" class="inline" onsubmit="return confirm('{{ __('Delete this ledger entry?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-300 group-hover:text-red-500 hover:!text-red-600 transition-colors duration-150" title="{{ __('Delete') }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="!px-6 !py-12 text-center">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-700">{{ __('No operational ledger entries yet.') }}</p>
                                    <p class="text-xs text-slate-500 mt-1">{{ __('Fees, costs, and firm receipts will appear here.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
