<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl text-slate-800">Financial Dashboard</h2>
            <a href="{{ route('billing.create-invoice') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Create Invoice
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 animate-fade-in-up stagger-1">
        <div class="glass-card p-6 border border-white/50 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition-colors duration-500"></div>
            <div class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Revenue (Operational)</div>
            <div class="text-4xl font-heading font-bold text-slate-800 font-mono tracking-tight">Rs. {{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div class="glass-card p-6 border border-white/50 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-colors duration-500"></div>
            <div class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Trust Balance</div>
            <div class="text-4xl font-heading font-bold text-emerald-600 font-mono tracking-tight">Rs. {{ number_format($totalTrust, 2) }}</div>
        </div>
        <div class="glass-card p-6 border border-white/50 relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-colors duration-500"></div>
            <div class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Active Cases</div>
            <div class="text-4xl font-heading font-bold text-amber-600 font-mono tracking-tight">{{ $totalCases }}</div>
        </div>
    </div>

    <div class="glass-card overflow-hidden animate-fade-in-up stagger-2">
        <div class="p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm flex items-center justify-between">
            <h3 class="heading-section !text-lg">Case Financial Summaries</h3>
            <div class="flex items-center gap-2">
                <a href="{{ route('billing.export-report') }}" class="btn-secondary !py-2 !px-4 !text-sm">Download Report</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table-dynamic">
                <thead>
                    <tr>
                        <th>Case / Client</th>
                        <th>Trial Dates</th>
                        <th class="text-right">Appearance Fees</th>
                        <th class="text-right">Trust Balance</th>
                        <th class="text-right">Operational Balance</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($caseSummaries as $summary)
                        <tr class="table-row-dynamic group">
                            <td>
                                <div class="font-medium text-slate-800">{{ $summary['case']->client->name ?? 'Unknown Client' }}</div>
                                <div class="text-xs text-indigo-600 font-mono mt-0.5">LEX-{{ $summary['case']->created_at->format('Y').'-'.str_pad($summary['case']->id, 3, '0', STR_PAD_LEFT) }}</div>
                            </td>
                            <td>
                                <span class="badge-dynamic bg-slate-100 text-slate-600 border border-slate-200">{{ $summary['trial_date_count'] }} dates</span>
                            </td>
                            <td class="text-right font-mono text-slate-600">
                                {{ number_format($summary['appearance_fee'], 2) }}
                            </td>
                            <td class="text-right">
                                <span class="font-mono font-medium {{ $summary['trust_balance'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    Rs. {{ number_format($summary['trust_balance'], 2) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <span class="font-mono font-medium {{ $summary['operational_balance'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    Rs. {{ number_format($summary['operational_balance'], 2) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('billing.case', $summary['case']) }}" class="btn-primary !py-1.5 !px-3 !text-xs transition-opacity">Manage Billing</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-4">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-slate-900">No cases found</h3>
                                <p class="mt-1 text-slate-500">Your financial summaries will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
