<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Legal Cases') }}
            </h2>
            @if(auth()->user()->role !== 'clerk')
                <a href="{{ route('cases.create') }}"
                   class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('New Case') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            {{-- Success Message --}}
            @if(session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
                    <div class="flex items-center">
                        <svg class="mr-2 h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            {{-- Filters --}}
            <div class="mb-6 overflow-hidden rounded-lg bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 p-6">
                    <form method="GET" action="{{ route('cases.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <x-input-label for="search" :value="__('Search')" />
                            <x-text-input id="search" name="search" type="text"
                                          class="mt-1 block w-full"
                                          placeholder="Search by client, attorney, or case type..."
                                          :value="request('search')" />
                        </div>
                        <div class="sm:w-48">
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                                <option value="active" @selected(request('status') === 'active')>Active</option>
                                <option value="trial_scheduled" @selected(request('status') === 'trial_scheduled')>Trial Scheduled</option>
                                <option value="judgment_delivered" @selected(request('status') === 'judgment_delivered')>Judgment Delivered</option>
                                <option value="case_closed" @selected(request('status') === 'case_closed')>Case Closed</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button type="submit">
                                {{ __('Filter') }}
                            </x-primary-button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('cases.index') }}"
                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Case Ref</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Attorney</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($cases as $case)
                                <tr class="transition hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        <div class="flex flex-col">
                                            <span class="font-mono text-xs font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded w-fit">
                                                LEX-{{ $case->created_at->format('Y') }}-{{ str_pad($case->id, 3, '0', STR_PAD_LEFT) }}
                                            </span>
                                            <span class="text-xs text-gray-400 mt-0.5">#{{ $case->id }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ $case->client->name ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ $case->assignedAttorney->name ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        {{ $case->case_type ?: 'General Legal' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        @php
                                            $badgeClasses = match($case->status) {
                                                'active' => 'bg-green-100 text-green-800',
                                                'pending' => 'bg-blue-100 text-blue-800',
                                                'trial_scheduled' => 'bg-amber-100 text-amber-800',
                                                'judgment_delivered' => 'bg-purple-100 text-purple-800',
                                                'case_closed' => 'bg-gray-100 text-gray-800',
                                                default => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold leading-5 {{ $badgeClasses }}">
                                            {{ str_replace('_', ' ', ucfirst($case->status)) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('cases.show', $case) }}"
                                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                                View
                                            </a>
                                            @if(auth()->user()->role !== 'clerk')
                                                <a href="{{ route('cases.edit', $case) }}"
                                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                                    Edit
                                                </a>
                                            @endif
                                            <a href="{{ route('cases.export-brief', $case) }}"
                                               class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition"
                                               title="Download PDF Brief">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                                PDF Brief
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg class="mb-4 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            <p class="text-sm font-medium text-gray-500">No cases found</p>
                                            <p class="mt-1 text-sm text-gray-400">Try adjusting your search or filter criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($cases->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $cases->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
