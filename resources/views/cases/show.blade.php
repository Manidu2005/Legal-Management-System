<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full flex-wrap gap-3">
            <div class="flex items-center gap-4">
                <a href="{{ route('cases.index') }}" class="text-mist-400 dark:text-mist-500 hover:text-mist-950 dark:hover:text-white transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-xl font-semibold leading-tight text-mist-950 dark:text-white">
                    {{ $case->display_name }}
                </h2>
                @php
                    $badgeClasses = match($case->status) {
                        'active' => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-400',
                        'pending' => 'bg-sky-100 dark:bg-sky-500/10 text-sky-800 dark:text-sky-400',
                        'trial_scheduled' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-800 dark:text-amber-400',
                        'judgment_delivered' => 'bg-mist-950/10 dark:bg-white/10 text-mist-800 dark:text-mist-200',
                        default => 'bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300',
                    };
                @endphp
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold leading-5 {{ $badgeClasses }}">
                    {{ __(str_replace('_', ' ', ucfirst($case->status))) }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <a href="https://www.lawnet.gov.lk"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center rounded-full border border-mist-950/10 dark:border-white/10 bg-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-mist-950 dark:text-white shadow-sm transition duration-150 ease-in-out hover:bg-mist-950/10 dark:hover:bg-white/10">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    {{ __('Search LawNet') }}
                </a>
                @if(auth()->user()->role !== 'clerk')
                <a href="{{ route('cases.edit', $case) }}"
                   class="inline-flex items-center rounded-full border border-mist-950/10 dark:border-white/10 bg-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-mist-950 dark:text-white shadow-sm transition duration-150 ease-in-out hover:bg-mist-950/10 dark:hover:bg-white/10">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    {{ __('Edit Case') }}
                </a>
                @endif
            </div>
        </div>
    </x-slot>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert-success mb-6">
            <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Case Overview --}}
    <div class="mb-6 glass-card overflow-hidden">
        <div class="border-b border-mist-950/10 dark:border-white/10 px-6 py-4">
            <h3 class="text-lg font-semibold text-mist-950 dark:text-white">{{ __('Case Overview') }}</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {{-- Client Info --}}
                <div class="rounded-lg border border-mist-950/10 dark:border-white/10 bg-mist-950/5 dark:bg-white/5 p-4">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Client Information') }}</h4>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Name') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ $case->client->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('NIC') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ $case->client->nic ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Phone') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ $case->client->phone ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Case Details --}}
                <div class="rounded-lg border border-mist-950/10 dark:border-white/10 bg-mist-950/5 dark:bg-white/5 p-4">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Case Details') }}</h4>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Case Name') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ $case->display_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Case ID') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">#{{ $case->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Case Type') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">
                                {{ $case->caseCategory ? $case->caseCategory->fullPath() : ($case->case_type_other ?? '—') }}
                            </dd>
                        </div>
                        @if($case->caseCategory && $case->caseCategory->name === 'Other' && $case->case_type_other)
                            <div>
                                <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Case Type Detail') }}</dt>
                                <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ $case->case_type_other }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Court / Forum') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ $case->court->name ?? '—' }}</dd>
                        </div>
                        @if($case->applicable_law)
                            <div>
                                <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Applicable Law') }}</dt>
                                <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ \App\Models\LegalCase::APPLICABLE_LAWS[$case->applicable_law] ?? $case->applicable_law }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Created') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ $case->created_at->format('M d, Y') }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Attorney --}}
                <div class="rounded-lg border border-mist-950/10 dark:border-white/10 bg-mist-950/5 dark:bg-white/5 p-4">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Assigned Attorney') }}</h4>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Name') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ $case->assignedAttorney->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Role') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ $case->assignedAttorney ? __(ucfirst($case->assignedAttorney->role)) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-mist-500 dark:text-mist-400">{{ __('Branch') }}</dt>
                            <dd class="text-sm font-medium text-mist-950 dark:text-white">{{ $case->assignedAttorney->branch ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabbed Sections --}}
    <div x-data="{ activeTab: (window.location.hash === '#related-judgments' || {{ session()->has('judgmentSearchResults') || session()->has('judgmentSearchQuery') ? 'true' : 'false' }}) ? 'related_judgments' : 'court_dates' }" class="glass-card overflow-hidden">
        {{-- Tab Navigation --}}
        <div class="border-b border-mist-950/10 dark:border-white/10">
            <nav class="-mb-px flex space-x-8 px-6 overflow-x-auto" aria-label="Tabs">
                <button @click="activeTab = 'court_dates'" type="button"
                        :class="activeTab === 'court_dates'
                            ? 'border-mist-950 dark:border-white text-mist-950 dark:text-white'
                            : 'border-transparent text-mist-500 dark:text-mist-400 hover:border-mist-300 dark:hover:border-mist-600 hover:text-mist-800 dark:hover:text-mist-200'"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition">
                    <svg class="-ml-0.5 mr-2 inline h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ __('Court Dates') }}
                    <span class="ml-2 rounded-full bg-mist-950/10 dark:bg-white/10 px-2 py-0.5 text-xs font-medium text-mist-700 dark:text-mist-300">
                        {{ $case->courtDates->count() }}
                    </span>
                </button>

                <button @click="activeTab = 'documents'" type="button"
                        :class="activeTab === 'documents'
                            ? 'border-mist-950 dark:border-white text-mist-950 dark:text-white'
                            : 'border-transparent text-mist-500 dark:text-mist-400 hover:border-mist-300 dark:hover:border-mist-600 hover:text-mist-800 dark:hover:text-mist-200'"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition">
                    <svg class="-ml-0.5 mr-2 inline h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('Documents') }}
                    <span class="ml-2 rounded-full bg-mist-950/10 dark:bg-white/10 px-2 py-0.5 text-xs font-medium text-mist-700 dark:text-mist-300">
                        {{ $case->documents->count() }}
                    </span>
                </button>

                <button @click="activeTab = 'research'" type="button"
                        :class="activeTab === 'research'
                            ? 'border-mist-950 dark:border-white text-mist-950 dark:text-white'
                            : 'border-transparent text-mist-500 dark:text-mist-400 hover:border-mist-300 dark:hover:border-mist-600 hover:text-mist-800 dark:hover:text-mist-200'"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition">
                    <svg class="-ml-0.5 mr-2 inline h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    {{ __('Research') }}
                    <span class="ml-2 rounded-full bg-mist-950/10 dark:bg-white/10 px-2 py-0.5 text-xs font-medium text-mist-700 dark:text-mist-300">
                        {{ $case->researchNotes->count() }}
                    </span>
                </button>

                <button @click="activeTab = 'related_judgments'; window.location.hash = 'related-judgments'" type="button"
                        :class="activeTab === 'related_judgments'
                            ? 'border-mist-950 dark:border-white text-mist-950 dark:text-white'
                            : 'border-transparent text-mist-500 dark:text-mist-400 hover:border-mist-300 dark:hover:border-mist-600 hover:text-mist-800 dark:hover:text-mist-200'"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition">
                    <svg class="-ml-0.5 mr-2 inline h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                    {{ __('Related Judgments') }}
                    <span class="ml-2 rounded-full bg-mist-950/10 dark:bg-white/10 px-2 py-0.5 text-xs font-medium text-mist-700 dark:text-mist-300">
                        {{ $case->caseJudgments->count() }}
                    </span>
                </button>

                @can('view-financials')
                    <button @click="activeTab = 'billing'" type="button"
                            :class="activeTab === 'billing'
                                ? 'border-mist-950 dark:border-white text-mist-950 dark:text-white'
                                : 'border-transparent text-mist-500 dark:text-mist-400 hover:border-mist-300 dark:hover:border-mist-600 hover:text-mist-800 dark:hover:text-mist-200'"
                            class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition">
                        <svg class="-ml-0.5 mr-2 inline h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('Billing') }}
                    </button>
                @endcan
            </nav>
        </div>

        {{-- Court Dates Tab --}}
        <div x-show="activeTab === 'court_dates'" class="p-6">
            @if($case->courtDates->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-mist-950/10 dark:divide-white/10">
                        <thead class="bg-mist-950/5 dark:bg-white/5">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Reminder') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                            @foreach($case->courtDates as $courtDate)
                                <tr class="transition hover:bg-mist-950/5 dark:hover:bg-white/5">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-mist-950 dark:text-white">
                                        {{ \Carbon\Carbon::parse($courtDate->date)->format('M d, Y \a\t h:i A') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        @php
                                            $typeBadge = $courtDate->type === 'trial_date'
                                                ? 'bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400'
                                                : 'bg-sky-100 dark:bg-sky-500/10 text-sky-800 dark:text-sky-400';
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeBadge }}">
                                            {{ __(str_replace('_', ' ', ucfirst($courtDate->type))) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        @if($courtDate->reminder_sent)
                                            <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400">
                                                <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                {{ __('Sent') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-mist-400 dark:text-mist-500">
                                                <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                                {{ __('Pending') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <svg class="mx-auto mb-4 h-12 w-12 text-mist-300 dark:text-mist-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('No court dates recorded') }}</p>
                    <p class="mt-1 text-sm text-mist-400 dark:text-mist-500">{{ __('Court dates will appear here once added.') }}</p>
                </div>
            @endif
        </div>

        {{-- Documents Tab --}}
        <div x-show="activeTab === 'documents'" x-cloak class="p-6">
            @if($case->documents->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-mist-950/10 dark:divide-white/10">
                        <thead class="bg-mist-950/5 dark:bg-white/5">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('File') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Category') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Uploaded By') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                            @foreach($case->documents as $document)
                                <tr class="transition hover:bg-mist-950/5 dark:hover:bg-white/5">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <div class="flex items-center">
                                            <svg class="mr-2 h-5 w-5 text-mist-400 dark:text-mist-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            <span class="font-medium text-mist-950 dark:text-white">{{ $document->display_name }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span class="inline-flex rounded-full bg-mist-950/10 dark:bg-white/10 px-2.5 py-0.5 text-xs font-semibold text-mist-900 dark:text-mist-100">
                                            {{ ucfirst($document->category) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-mist-700 dark:text-mist-300">
                                        {{ $document->uploadedBy->name ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-mist-500 dark:text-mist-400">
                                        {{ $document->created_at->format('M d, Y') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <svg class="mx-auto mb-4 h-12 w-12 text-mist-300 dark:text-mist-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('No documents uploaded') }}</p>
                    <p class="mt-1 text-sm text-mist-400 dark:text-mist-500">{{ __('Documents will appear here once uploaded.') }}</p>
                </div>
            @endif
        </div>

        {{-- Research Tab --}}
        <div x-show="activeTab === 'research'" x-cloak class="p-6" id="research">
            {{-- Add Research Note Form --}}
            <div class="mb-6 rounded-lg border border-mist-950/10 dark:border-white/10 bg-mist-950/5 dark:bg-white/5 p-4">
                <h4 class="mb-4 text-sm font-semibold text-mist-800 dark:text-mist-200">{{ __('Add Research Note') }}</h4>
                <form method="POST" action="{{ route('research-notes.store') }}">
                    @csrf
                    <input type="hidden" name="case_id" value="{{ $case->id }}">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="category" class="block text-xs font-medium text-mist-600 dark:text-mist-400">{{ __('Category') }} *</label>
                            <select name="category" id="category" required class="input-dynamic mt-1 !text-sm">
                                <option value="">{{ __('Select category…') }}</option>
                                @foreach($researchCategories as $cat)
                                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                                        {{ __(ucfirst(str_replace('_', ' ', $cat))) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="citation" class="block text-xs font-medium text-mist-600 dark:text-mist-400">{{ __('Citation') }} *</label>
                            <input type="text" name="citation" id="citation" value="{{ old('citation') }}" required
                                   placeholder="{{ __('e.g. Silva v. Perera [2020] LKSC 45') }}"
                                   class="input-dynamic mt-1 !text-sm">
                            @error('citation')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="court_or_source" class="block text-xs font-medium text-mist-600 dark:text-mist-400">{{ __('Court / Source') }}</label>
                            <input type="text" name="court_or_source" id="court_or_source" value="{{ old('court_or_source') }}"
                                   placeholder="{{ __('e.g. Supreme Court of Sri Lanka') }}"
                                   class="input-dynamic mt-1 !text-sm">
                            @error('court_or_source')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="source_url" class="block text-xs font-medium text-mist-600 dark:text-mist-400">{{ __('Source URL') }}</label>
                            <input type="url" name="source_url" id="source_url" value="{{ old('source_url') }}"
                                   placeholder="https://…"
                                   class="input-dynamic mt-1 !text-sm">
                            @error('source_url')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="note" class="block text-xs font-medium text-mist-600 dark:text-mist-400">{{ __('Note — why is this relevant?') }} *</label>
                        <textarea name="note" id="note" rows="3" required class="input-dynamic mt-1 !text-sm">{{ old('note') }}</textarea>
                        @error('note')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="btn-primary !py-2 !px-4 !text-xs !uppercase !tracking-widest">
                            {{ __('Add Note') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Research Notes List --}}
            @if($case->researchNotes->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-mist-950/10 dark:divide-white/10">
                        <thead class="bg-mist-950/5 dark:bg-white/5">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Category') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Citation') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Court / Source') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Note') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Added By') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                            @foreach($case->researchNotes as $researchNote)
                                <tr class="transition hover:bg-mist-950/5 dark:hover:bg-white/5">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        @php
                                            $catBadge = match($researchNote->category) {
                                                'judgment' => 'bg-mist-950/10 dark:bg-white/10 text-mist-800 dark:text-mist-200',
                                                'act_or_ordinance' => 'bg-sky-100 dark:bg-sky-500/10 text-sky-800 dark:text-sky-400',
                                                default => 'bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $catBadge }}">
                                            {{ __(ucfirst(str_replace('_', ' ', $researchNote->category))) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-mist-950 dark:text-white">
                                        @if($researchNote->source_url)
                                            <a href="{{ $researchNote->source_url }}" target="_blank" rel="noopener noreferrer"
                                               class="text-mist-700 dark:text-mist-300 hover:text-mist-950 dark:hover:text-white hover:underline">
                                                {{ $researchNote->citation }}
                                                <svg class="ml-1 inline h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </a>
                                        @else
                                            {{ $researchNote->citation }}
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-mist-700 dark:text-mist-300">
                                        {{ $researchNote->court_or_source ?? '—' }}
                                    </td>
                                    <td class="max-w-xs px-6 py-4 text-sm text-mist-700 dark:text-mist-300">
                                        <p class="truncate" title="{{ $researchNote->note }}">{{ Str::limit($researchNote->note, 120) }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-mist-700 dark:text-mist-300">
                                        {{ $researchNote->addedBy->name ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-mist-500 dark:text-mist-400">
                                        {{ $researchNote->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        @if($researchNote->added_by === auth()->id() || Gate::allows('manage-users'))
                                            <form method="POST" action="{{ route('research-notes.destroy', $researchNote) }}"
                                                  onsubmit="return confirm('{{ __('Delete this research note?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 dark:text-red-400 transition hover:text-red-700 dark:hover:text-red-300">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <svg class="mx-auto mb-4 h-12 w-12 text-mist-300 dark:text-mist-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <p class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('No research notes yet') }}</p>
                    <p class="mt-1 text-sm text-mist-400 dark:text-mist-500">{{ __('Add judgments, acts, or other references relevant to this case.') }}</p>
                </div>
            @endif
        </div>

        {{-- Related Judgments Tab --}}
        <div x-show="activeTab === 'related_judgments'" x-cloak class="p-6" id="related-judgments">
            @php
                $attachedJudgmentIds = $case->caseJudgments->pluck('judgment_id')->all();
                $searchQuery = session('judgmentSearchQuery', old('query'));
                $searchResults = session('judgmentSearchResults', []);
            @endphp

            {{-- Semantic search --}}
            <div class="mb-6 rounded-lg border border-mist-950/10 dark:border-white/10 bg-mist-950/5 dark:bg-white/5 p-4">
                <h4 class="mb-2 text-sm font-semibold text-mist-800 dark:text-mist-200">{{ __('Find Related Judgments') }}</h4>
                <p class="mb-4 text-xs text-mist-500 dark:text-mist-400">{{ __('Describe the facts or legal issue. We’ll rank the firm judgment library by semantic similarity.') }}</p>
                <form method="POST" action="{{ route('cases.related-judgments.search', $case) }}">
                    @csrf
                    <label for="judgment_search_query" class="block text-xs font-medium text-mist-600 dark:text-mist-400">{{ __('Search query') }} *</label>
                    <textarea name="query" id="judgment_search_query" rows="3" required
                              placeholder="{{ __('e.g. dispute over adverse possession of rural land under the Prescription Ordinance') }}"
                              class="input-dynamic mt-1 !text-sm">{{ $searchQuery }}</textarea>
                    @error('query')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="btn-primary !py-2 !px-4 !text-xs !uppercase !tracking-widest">
                            {{ __('Search Library') }}
                        </button>
                    </div>
                </form>
            </div>

            @if(! empty($searchResults))
                <div class="mb-8">
                    <h4 class="mb-3 text-sm font-semibold text-mist-800 dark:text-mist-200">{{ __('Search Results') }}</h4>
                    <div class="space-y-4">
                        @foreach($searchResults as $result)
                            @php
                                $citedActs = is_array($result['cited_acts'] ?? null) ? $result['cited_acts'] : [];
                                $categoryLabel = $result['category']
                                    ? str_replace('_', ' ', ucfirst($result['category']))
                                    : null;
                            @endphp
                            <div x-data="{ showFull: false }"
                                 x-init="$watch('showFull', value => document.body.classList.toggle('overflow-y-hidden', value))"
                                 class="rounded-lg border border-mist-950/10 dark:border-white/10 bg-white dark:bg-white/5 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h5 class="text-sm font-semibold text-mist-950 dark:text-white">{{ $result['title'] ?: __('(Untitled)') }}</h5>
                                            <span class="inline-flex rounded-full bg-mist-950/5 dark:bg-white/10 px-2 py-0.5 text-xs font-medium text-mist-800 dark:text-mist-200">
                                                {{ number_format(($result['similarity'] ?? 0) * 100, 1) }}% {{ __('match') }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">{{ $result['court'] ?: '—' }}</p>
                                        @if(! empty($result['summary']))
                                            <p class="mt-2 text-sm text-mist-600 dark:text-mist-300">{{ \Illuminate\Support\Str::limit($result['summary'], 220) }}</p>
                                        @endif
                                        @if(! empty($citedActs))
                                            <p class="mt-1 text-xs text-mist-700 dark:text-mist-300">
                                                {{ __('Cited:') }} {{ \Illuminate\Support\Str::limit(implode('; ', $citedActs), 160) }}
                                            </p>
                                        @endif
                                        <button type="button"
                                                @click="showFull = true"
                                                class="mt-3 text-xs font-semibold text-sky-600 dark:text-sky-400 transition hover:text-sky-800 dark:hover:text-sky-300">
                                            {{ __('See more') }}
                                        </button>
                                    </div>
                                    <div class="shrink-0">
                                        @if(in_array($result['id'], $attachedJudgmentIds, true))
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">{{ __('Attached') }}</span>
                                        @else
                                            <form method="POST" action="{{ route('cases.related-judgments.store', $case) }}" class="space-y-2">
                                                @csrf
                                                <input type="hidden" name="judgment_id" value="{{ $result['id'] }}">
                                                <input type="text" name="relevance_note"
                                                       placeholder="{{ __('Relevance note (optional)') }}"
                                                       class="input-dynamic !text-xs !py-1.5 sm:w-56">
                                                <button type="submit" class="btn-secondary w-full !py-1.5 !px-3 !text-xs justify-center">
                                                    {{ __('Attach to this case') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <template x-teleport="body">
                                    <div x-show="showFull"
                                         x-cloak
                                         x-transition:enter="ease-out duration-200"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         x-transition:leave="ease-in duration-150"
                                         x-transition:leave-start="opacity-100"
                                         x-transition:leave-end="opacity-0"
                                         class="fixed inset-0 z-[70] overflow-y-auto"
                                         role="dialog"
                                         aria-modal="true"
                                         aria-labelledby="judgment-full-title-{{ $result['id'] }}"
                                         @keydown.escape.window="showFull = false">
                                        <div class="flex min-h-full items-end justify-center p-4 sm:items-center sm:p-6">
                                            <div class="fixed inset-0 bg-mist-950/50 dark:bg-mist-950/80" @click="showFull = false"></div>
                                            <div class="relative z-10 mb-6 w-full max-w-3xl overflow-hidden rounded-lg border border-mist-950/10 bg-mist-100 shadow-xl dark:border-white/10 dark:bg-mist-950 sm:mb-0"
                                                 @click.stop>
                                                <div class="flex items-start justify-between gap-4 border-b border-mist-950/10 px-5 py-4 dark:border-white/10">
                                                    <div class="min-w-0">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <h3 id="judgment-full-title-{{ $result['id'] }}" class="text-lg font-semibold text-mist-950 dark:text-white">
                                                                {{ $result['title'] ?: __('(Untitled)') }}
                                                            </h3>
                                                            <span class="inline-flex rounded-full bg-mist-950/5 px-2 py-0.5 text-xs font-medium text-mist-800 dark:bg-white/10 dark:text-mist-200">
                                                                {{ number_format(($result['similarity'] ?? 0) * 100, 1) }}% {{ __('match') }}
                                                            </span>
                                                        </div>
                                                        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ __('Full judgment details') }}</p>
                                                    </div>
                                                    <button type="button"
                                                            @click="showFull = false"
                                                            class="rounded-lg p-1.5 text-mist-400 transition hover:bg-mist-950/5 hover:text-mist-800 dark:text-mist-500 dark:hover:bg-white/10 dark:hover:text-white"
                                                            aria-label="{{ __('Close') }}">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <div class="max-h-[70vh] space-y-5 overflow-y-auto px-5 py-5">
                                                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                        <div>
                                                            <dt class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Court') }}</dt>
                                                            <dd class="mt-1 text-sm text-mist-950 dark:text-white">{{ $result['court'] ?: '—' }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Decided date') }}</dt>
                                                            <dd class="mt-1 text-sm text-mist-950 dark:text-white">{{ $result['decided_date'] ?: '—' }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Category') }}</dt>
                                                            <dd class="mt-1 text-sm text-mist-950 dark:text-white">{{ $categoryLabel ?: '—' }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Source') }}</dt>
                                                            <dd class="mt-1 text-sm text-mist-950 dark:text-white">{{ $result['source_label'] ?: '—' }}</dd>
                                                        </div>
                                                        @if(! empty($result['source_start_page']) && ! empty($result['source_end_page']))
                                                            <div>
                                                                <dt class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Source pages') }}</dt>
                                                                <dd class="mt-1 text-sm text-mist-950 dark:text-white">
                                                                    {{ __('Pages :start–:end', ['start' => $result['source_start_page'], 'end' => $result['source_end_page']]) }}
                                                                </dd>
                                                            </div>
                                                        @endif
                                                    </dl>

                                                    <div>
                                                        <h4 class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Summary') }}</h4>
                                                        @if(! empty($result['summary']))
                                                            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-mist-700 dark:text-mist-200">{{ $result['summary'] }}</p>
                                                        @else
                                                            <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">{{ __('No summary available.') }}</p>
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <h4 class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Cited legislation') }}</h4>
                                                        @if(! empty($citedActs))
                                                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-mist-700 dark:text-mist-200">
                                                                @foreach($citedActs as $act)
                                                                    <li>{{ $act }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">{{ __('No cited legislation recorded.') }}</p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-mist-950/10 px-5 py-4 dark:border-white/10">
                                                    <div class="flex flex-wrap items-center gap-3">
                                                        @if(! empty($result['has_pdf']))
                                                            <a href="{{ route('judgments.download', $result['id']) }}"
                                                               class="text-sm font-medium text-sky-600 dark:text-sky-400 transition hover:text-sky-800 dark:hover:text-sky-300">
                                                                {{ __('Download PDF') }}
                                                            </a>
                                                        @elseif(! empty($result['source_url']))
                                                            <a href="{{ route('judgments.download', $result['id']) }}"
                                                               class="text-sm font-medium text-sky-600 dark:text-sky-400 transition hover:text-sky-800 dark:hover:text-sky-300">
                                                                {{ __('Open source') }}
                                                            </a>
                                                        @endif
                                                    </div>
                                                    <button type="button" @click="showFull = false" class="btn-secondary !py-1.5 !px-4 !text-xs">
                                                        {{ __('Close') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Already attached --}}
            <h4 class="mb-3 text-sm font-semibold text-mist-800 dark:text-mist-200">{{ __('Attached to this case') }}</h4>
            @if($case->caseJudgments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-mist-950/10 dark:divide-white/10">
                        <thead class="bg-mist-950/5 dark:bg-white/5">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Title') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Court') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Relevance') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Added By') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                            @foreach($case->caseJudgments as $attachment)
                                <tr class="transition hover:bg-mist-950/5 dark:hover:bg-white/5">
                                    <td class="px-6 py-4 text-sm font-medium text-mist-950 dark:text-white">
                                        {{ $attachment->judgment->title ?? __('(Untitled)') }}
                                        @if($attachment->judgment?->summary)
                                            <p class="mt-1 text-xs font-normal text-mist-500 dark:text-mist-400">{{ \Illuminate\Support\Str::limit($attachment->judgment->summary, 100) }}</p>
                                        @endif
                                        @if(! empty($attachment->judgment?->cited_acts))
                                            <p class="mt-1 text-xs font-normal text-mist-700 dark:text-mist-300">
                                                {{ __('Cited:') }} {{ \Illuminate\Support\Str::limit(implode('; ', $attachment->judgment->cited_acts), 120) }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-mist-700 dark:text-mist-300">
                                        {{ $attachment->judgment->court ?? '—' }}
                                    </td>
                                    <td class="max-w-xs px-6 py-4 text-sm text-mist-700 dark:text-mist-300">
                                        {{ $attachment->relevance_note ? \Illuminate\Support\Str::limit($attachment->relevance_note, 120) : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-mist-700 dark:text-mist-300">
                                        {{ $attachment->addedBy->name ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <div class="flex items-center justify-end gap-3">
                                            @if($attachment->judgment)
                                                <a href="{{ route('judgments.download', $attachment->judgment) }}"
                                                   class="text-sky-600 dark:text-sky-400 transition hover:text-sky-800 dark:hover:text-sky-300">
                                                    {{ __('Download PDF') }}
                                                </a>
                                            @endif
                                            @if($attachment->added_by === auth()->id() || Gate::allows('manage-users'))
                                                <form method="POST" action="{{ route('cases.related-judgments.destroy', [$case, $attachment]) }}"
                                                      onsubmit="return confirm('{{ __('Remove this judgment from the case?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 dark:text-red-400 transition hover:text-red-700 dark:hover:text-red-300">
                                                        {{ __('Remove') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <p class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('No judgments attached yet') }}</p>
                    <p class="mt-1 text-sm text-mist-400 dark:text-mist-500">{{ __('Search the library above and attach relevant judgments.') }}</p>
                </div>
            @endif
        </div>

        {{-- Billing Tab --}}
        @can('view-financials')
            <div x-show="activeTab === 'billing'" x-cloak class="p-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Appearance Fee Card --}}
                    <div class="rounded-lg border border-mist-950/10 dark:border-white/10 bg-gradient-to-br from-mist-100 to-mist-50 dark:from-mist-900 dark:to-mist-950 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('Total Appearance Fee') }}</p>
                                <p class="mt-2 text-3xl font-sans font-semibold text-mist-950 dark:text-white">
                                    LKR {{ number_format($totalAppearanceFee, 2) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-mist-950/10 dark:bg-white/10 p-3">
                                <svg class="h-6 w-6 text-mist-700 dark:text-mist-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-mist-400 dark:text-mist-500">
                            {{ __(':count court date(s) × LKR :rate per appearance', ['count' => $case->courtDates->count(), 'rate' => number_format($case->assignedAttorney->flat_appearance_rate ?? 0, 2)]) }}
                        </p>
                    </div>

                    {{-- Ledger Total Card --}}
                    <div class="rounded-lg border border-mist-950/10 dark:border-white/10 bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-500/10 dark:to-white/5 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('Ledger Total') }}</p>
                                <p class="mt-2 text-3xl font-sans font-semibold text-emerald-600 dark:text-emerald-400">
                                    LKR {{ number_format($ledgerTotal, 2) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-emerald-100 dark:bg-emerald-500/10 p-3">
                                <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-mist-400 dark:text-mist-500">
                            {{ __(':count ledger entries', ['count' => $case->ledgerEntries->count()]) }}
                        </p>
                    </div>
                </div>

                {{-- Ledger Entries Table --}}
                @if($case->ledgerEntries->count() > 0)
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-mist-950/10 dark:divide-white/10">
                            <thead class="bg-mist-950/5 dark:bg-white/5">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Date') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Type') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Description') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Recorded By') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                                @foreach($case->ledgerEntries as $entry)
                                    <tr class="transition hover:bg-mist-950/5 dark:hover:bg-white/5">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-mist-950 dark:text-white">
                                            {{ $entry->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            @php
                                                $ledgerBadge = $entry->type === 'trust'
                                                    ? 'bg-sky-100 dark:bg-sky-500/10 text-sky-800 dark:text-sky-400'
                                                    : 'bg-amber-100 dark:bg-amber-500/10 text-amber-800 dark:text-amber-400';
                                            @endphp
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $ledgerBadge }}">
                                                {{ __(ucfirst($entry->type)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-mist-700 dark:text-mist-300">
                                            {{ $entry->description ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-mist-700 dark:text-mist-300">
                                            {{ $entry->recordedBy->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-mist-950 dark:text-white">
                                            LKR {{ number_format($entry->amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endcan
    </div>
</x-app-layout>
