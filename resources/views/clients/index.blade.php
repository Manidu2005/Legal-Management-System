<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl text-slate-800">Client Profiles</h2>
            <a href="{{ route('clients.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Register Client
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
        <div class="glass-card p-6 border border-white/50">
            <div class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Clients</div>
            <div class="text-4xl font-heading font-bold text-slate-800">{{ $clients->total() }}</div>
        </div>
    </div>

    <div class="glass-card overflow-hidden animate-fade-in-up stagger-2">
        <div class="p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm flex items-center justify-between">
            <div class="relative w-72">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" placeholder="Search clients..." class="input-dynamic pl-10 !py-2 !text-sm">
            </div>
            <button class="btn-secondary !py-2 !px-4 !text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filter
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="table-dynamic">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>NIC Number</th>
                        <th>Active Cases</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($clients as $client)
                        <tr class="table-row-dynamic group">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-sky-400 to-indigo-500 flex items-center justify-center text-white font-bold shadow-md shadow-indigo-500/20">
                                        {{ substr($client->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-800">{{ $client->name }}</div>
                                        <div class="text-xs text-slate-400 mt-0.5">Added {{ $client->created_at->format('M Y') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-slate-600">{{ $client->email }}</div>
                                <div class="text-slate-500 text-xs mt-0.5">{{ $client->phone }}</div>
                            </td>
                            <td>
                                <span class="font-mono text-sm text-slate-500 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                                    {{ $client->nic ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <div class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    {{ $client->legal_cases_count ?? 0 }} cases
                                </div>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2 transition-opacity duration-200">
                                    <a href="{{ route('clients.show', $client) }}" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="View Profile">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('clients.edit', $client) }}" class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline" onsubmit="return confirm('Delete this client? This cannot be undone and will delete all associated cases and documents.');">
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
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($clients->hasPages())
            <div class="px-6 py-4 border-t border-slate-200/60 bg-slate-50/50">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
