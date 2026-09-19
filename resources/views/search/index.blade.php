<x-app-layout>
    <x-slot name="header">
        <h2 class="heading-display !text-3xl">
            {{ __('Search') }}
        </h2>
    </x-slot>

    {{-- Search Bar --}}
    <div class="glass-card overflow-hidden mb-8">
        <div class="p-6">
            <form method="GET" action="{{ route('search.index') }}">
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none">
                        <svg class="w-5 h-5 text-mist-400 dark:text-mist-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input type="text" name="q" value="{{ $query }}"
                        placeholder="{{ __('Search clients, cases, documents, or judgments...') }}"
                        class="input-dynamic block w-full ps-12 pe-4 py-3 !text-base"
                        autofocus />
                </div>
            </form>
        </div>
    </div>

    @if ($query !== '')
        @php
            $totalResults = $clients->count() + $cases->count() + $documents->count() + $judgments->count();
        @endphp

        @if ($totalResults === 0)
            {{-- No Results --}}
            <div class="glass-card overflow-hidden">
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-mist-400 dark:text-mist-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-mist-950 dark:text-white">{{ __('No results found') }}</h3>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ __('No matches for ":query". Try a different search term.', ['query' => $query]) }}</p>
                </div>
            </div>
        @else
            <p class="text-sm text-mist-500 dark:text-mist-400 mb-6">{{ __('Found :count result(s) for ":query"', ['count' => $totalResults, 'query' => $query]) }}</p>

            {{-- Clients --}}
            @if ($clients->isNotEmpty())
                <div class="glass-card overflow-hidden mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-mist-950 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 me-2 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            {{ __('Clients') }}
                            <span class="ms-2 text-sm font-normal text-mist-500 dark:text-mist-400">({{ $clients->count() }})</span>
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="table-dynamic">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('NIC') }}</th>
                                        <th>{{ __('Phone') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                                    @foreach ($clients as $client)
                                        <tr class="table-row-dynamic">
                                            <td class="text-sm font-medium text-mist-950 dark:text-white">{{ $client->name }}</td>
                                            <td class="text-sm text-mist-500 dark:text-mist-400">{{ $client->nic }}</td>
                                            <td class="text-sm text-mist-500 dark:text-mist-400">{{ $client->phone ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Cases --}}
            @if ($cases->isNotEmpty())
                <div class="glass-card overflow-hidden mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-mist-950 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 me-2 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            {{ __('Cases') }}
                            <span class="ms-2 text-sm font-normal text-mist-500 dark:text-mist-400">({{ $cases->count() }})</span>
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="table-dynamic">
                                <thead>
                                    <tr>
                                        <th>{{ __('Case Name') }}</th>
                                        <th>{{ __('Client') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                                    @foreach ($cases as $case)
                                        <tr class="table-row-dynamic">
                                            <td class="text-sm font-medium">
                                                <a href="{{ route('cases.show', $case) }}" class="link-subtle">{{ $case->display_name }}</a>
                                            </td>
                                            <td class="text-sm text-mist-950 dark:text-white">{{ $case->client->name ?? '—' }}</td>
                                            <td class="text-sm text-mist-500 dark:text-mist-400">{{ $case->caseCategory->name ?? $case->case_type_other ?? '—' }}</td>
                                            <td class="text-sm">
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-800 dark:text-amber-400',
                                                        'active' => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-400',
                                                        'trial_scheduled' => 'bg-sky-100 dark:bg-sky-500/10 text-sky-800 dark:text-sky-400',
                                                        'judgment_delivered' => 'bg-violet-100 dark:bg-violet-500/10 text-violet-800 dark:text-violet-400',
                                                        'case_closed' => 'bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300',
                                                    ];
                                                @endphp
                                                <span class="badge-dynamic border-0 {{ $statusColors[$case->status] ?? 'bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300' }}">
                                                    {{ $case->status ? __(ucfirst(str_replace('_', ' ', $case->status))) : '—' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Documents --}}
            @if ($documents->isNotEmpty())
                <div class="glass-card overflow-hidden mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-mist-950 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 me-2 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            {{ __('Documents') }}
                            <span class="ms-2 text-sm font-normal text-mist-500 dark:text-mist-400">({{ $documents->count() }})</span>
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="table-dynamic">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Case') }}</th>
                                        <th>{{ __('Category') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                                    @foreach ($documents as $document)
                                        <tr class="table-row-dynamic">
                                            <td class="text-sm">
                                                <a href="{{ route('documents.show', $document) }}" class="link-subtle font-medium">
                                                    {{ $document->display_name }}
                                                </a>
                                            </td>
                                            <td class="text-sm text-mist-500 dark:text-mist-400">
                                                @if ($document->legalCase)
                                                    {{ $document->legalCase->display_name }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-sm">
                                                @php
                                                    $badgeColors = [
                                                        'evidence' => 'bg-sky-100 dark:bg-sky-500/10 text-sky-800 dark:text-sky-400',
                                                        'deeds' => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-400',
                                                        'correspondence' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-800 dark:text-amber-400',
                                                    ];
                                                @endphp
                                                <span class="badge-dynamic border-0 {{ $badgeColors[$document->category] ?? 'bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300' }}">
                                                    {{ ucfirst($document->category) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if ($judgments->isNotEmpty())
                <div class="glass-card overflow-hidden mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-mist-950 dark:text-white mb-4">
                            {{ __('Judgments') }}
                            <span class="ms-2 text-sm font-normal text-mist-500 dark:text-mist-400">({{ $judgments->count() }})</span>
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="table-dynamic">
                                <thead>
                                    <tr>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Court') }}</th>
                                        <th>{{ __('Date') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                                    @foreach ($judgments as $judgment)
                                        <tr class="table-row-dynamic">
                                            <td class="text-sm font-medium text-mist-950 dark:text-white">
                                                <a href="{{ route('judgments.index', ['q' => $judgment->title]) }}" class="link-subtle">
                                                    {{ $judgment->title ?: __('(Untitled)') }}
                                                </a>
                                            </td>
                                            <td class="text-sm text-mist-500 dark:text-mist-400">{{ $judgment->court ?: '—' }}</td>
                                            <td class="text-sm text-mist-500 dark:text-mist-400">{{ $judgment->decided_date?->format('d M Y') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endif
</x-app-layout>
