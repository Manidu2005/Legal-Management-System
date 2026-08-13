<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl text-slate-800">Cases Registry</h2>
            <a href="{{ route('cases.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                New Case
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

    <div class="glass-card overflow-hidden">
        <div class="p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm flex items-center justify-between">
            <div class="relative w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" placeholder="Search cases..." class="input-dynamic pl-10 !py-2 !text-sm">
            </div>
            <div class="flex items-center gap-2">
                <button class="btn-secondary !py-2 !px-4 !text-sm">Filter</button>
                <button class="btn-secondary !py-2 !px-4 !text-sm">Export</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table-dynamic">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Case Type</th>
                        <th>Client</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($cases as $case)
                        <tr class="table-row-dynamic group">
                            <td>
                                <div class="flex items-center gap-2 font-mono text-xs font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-100 w-fit">
                                    LEX-{{ $case->created_at->format('Y') }}-{{ str_pad($case->id, 3, '0', STR_PAD_LEFT) }}
                                </div>
                            </td>
                            <td>
                                <div class="font-medium text-slate-800">{{ $case->case_type }}</div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center text-xs font-bold">
                                        {{ substr($case->client->name ?? '?', 0, 1) }}
                                    </div>
                                    <span class="text-slate-600 font-medium">{{ $case->client->name ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $statusConfig = match($case->status) {
                                        'active' => ['bg-emerald-100', 'text-emerald-700', 'border-emerald-200'],
                                        'pending' => ['bg-amber-100', 'text-amber-700', 'border-amber-200'],
                                        'trial' => ['bg-purple-100', 'text-purple-700', 'border-purple-200'],
                                        'judgment' => ['bg-indigo-100', 'text-indigo-700', 'border-indigo-200'],
                                        'closed' => ['bg-slate-100', 'text-slate-600', 'border-slate-200'],
                                        default => ['bg-slate-100', 'text-slate-600', 'border-slate-200'],
                                    };
                                @endphp
                                <span class="badge-dynamic {{ $statusConfig[0] }} {{ $statusConfig[1] }} border {{ $statusConfig[2] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ str_replace('text', 'bg', $statusConfig[1]) }} mr-1.5"></span>
                                    {{ ucfirst($case->status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2 transition-opacity duration-200">
                                    <a href="{{ route('cases.show', $case) }}" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="View">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('cases.edit', $case) }}" class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('cases.destroy', $case) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this case?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
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
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-4">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-slate-900">No cases found</h3>
                                <p class="mt-1 text-slate-500">Get started by opening your first case.</p>
                                <div class="mt-6">
                                    <a href="{{ route('cases.create') }}" class="btn-primary">
                                        Open New Case
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($cases) && $cases->hasPages())
            <div class="px-6 py-4 border-t border-slate-200/60 bg-slate-50/50">
                {{ $cases->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
