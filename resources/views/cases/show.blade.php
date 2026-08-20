<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('cases.index') }}" class="text-gray-400 transition hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ $case->display_name }}
                </h2>
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
                    {{ __(str_replace('_', ' ', ucfirst($case->status))) }}
                </span>
            </div>
            @if(auth()->user()->role !== 'clerk')
                <a href="{{ route('cases.edit', $case) }}"
                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    {{ __('Edit Case') }}
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

            {{-- Case Overview --}}
            <div class="mb-6 overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('Case Overview') }}</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {{-- Client Info --}}
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Client Information') }}</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-xs text-gray-500">{{ __('Name') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $case->client->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">{{ __('NIC') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $case->client->nic ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">{{ __('Phone') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $case->client->phone ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Case Details --}}
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Case Details') }}</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-xs text-gray-500">{{ __('Case Name') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $case->display_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">{{ __('Case ID') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">#{{ $case->id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">{{ __('Case Type') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $case->case_type ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">{{ __('Created') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $case->created_at->format('M d, Y') }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Attorney --}}
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Assigned Attorney') }}</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-xs text-gray-500">{{ __('Name') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $case->assignedAttorney->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">{{ __('Role') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $case->assignedAttorney ? __(ucfirst($case->assignedAttorney->role)) : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">{{ __('Branch') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ $case->assignedAttorney->branch ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabbed Sections --}}
            <div x-data="{ activeTab: 'court_dates' }" class="overflow-hidden rounded-lg bg-white shadow-sm">
                {{-- Tab Navigation --}}
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button @click="activeTab = 'court_dates'" type="button"
                                :class="activeTab === 'court_dates'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition">
                            <svg class="-ml-0.5 mr-2 inline h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ __('Court Dates') }}
                            <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                {{ $case->courtDates->count() }}
                            </span>
                        </button>

                        <button @click="activeTab = 'documents'" type="button"
                                :class="activeTab === 'documents'
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition">
                            <svg class="-ml-0.5 mr-2 inline h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            {{ __('Documents') }}
                            <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                {{ $case->documents->count() }}
                            </span>
                        </button>

                        @can('view-financials')
                            <button @click="activeTab = 'billing'" type="button"
                                    :class="activeTab === 'billing'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
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
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Date') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Type') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Reminder') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($case->courtDates as $courtDate)
                                        <tr class="transition hover:bg-gray-50">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($courtDate->date)->format('M d, Y \a\t h:i A') }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                @php
                                                    $typeBadge = $courtDate->type === 'trial_date'
                                                        ? 'bg-red-100 text-red-800'
                                                        : 'bg-sky-100 text-sky-800';
                                                @endphp
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $typeBadge }}">
                                                    {{ __(str_replace('_', ' ', ucfirst($courtDate->type))) }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                @if($courtDate->reminder_sent)
                                                    <span class="inline-flex items-center text-green-600">
                                                        <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                        {{ __('Sent') }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center text-gray-400">
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
                            <svg class="mx-auto mb-4 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <p class="text-sm font-medium text-gray-500">{{ __('No court dates recorded') }}</p>
                            <p class="mt-1 text-sm text-gray-400">{{ __('Court dates will appear here once added.') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Documents Tab --}}
                <div x-show="activeTab === 'documents'" x-cloak class="p-6">
                    @if($case->documents->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('File') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Category') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Uploaded By') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Date') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($case->documents as $document)
                                        <tr class="transition hover:bg-gray-50">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                <div class="flex items-center">
                                                    <svg class="mr-2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                    <span class="font-medium text-gray-900">{{ $document->display_name }}</span>
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800">
                                                    {{ ucfirst($document->category) }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                                {{ $document->uploadedBy->name ?? '—' }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                {{ $document->created_at->format('M d, Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="py-12 text-center">
                            <svg class="mx-auto mb-4 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="text-sm font-medium text-gray-500">{{ __('No documents uploaded') }}</p>
                            <p class="mt-1 text-sm text-gray-400">{{ __('Documents will appear here once uploaded.') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Billing Tab --}}
                @can('view-financials')
                    <div x-show="activeTab === 'billing'" x-cloak class="p-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            {{-- Appearance Fee Card --}}
                            <div class="rounded-lg border border-gray-200 bg-gradient-to-br from-indigo-50 to-white p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">{{ __('Total Appearance Fee') }}</p>
                                        <p class="mt-2 text-3xl font-bold text-indigo-600">
                                            LKR {{ number_format($totalAppearanceFee, 2) }}
                                        </p>
                                    </div>
                                    <div class="rounded-full bg-indigo-100 p-3">
                                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-400">
                                    {{ __(':count court date(s) × LKR :rate per appearance', ['count' => $case->courtDates->count(), 'rate' => number_format($case->assignedAttorney->flat_appearance_rate ?? 0, 2)]) }}
                                </p>
                            </div>

                            {{-- Ledger Total Card --}}
                            <div class="rounded-lg border border-gray-200 bg-gradient-to-br from-emerald-50 to-white p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">{{ __('Ledger Total') }}</p>
                                        <p class="mt-2 text-3xl font-bold text-emerald-600">
                                            LKR {{ number_format($ledgerTotal, 2) }}
                                        </p>
                                    </div>
                                    <div class="rounded-full bg-emerald-100 p-3">
                                        <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-400">
                                    {{ __(':count ledger entries', ['count' => $case->ledgerEntries->count()]) }}
                                </p>
                            </div>
                        </div>

                        {{-- Ledger Entries Table --}}
                        @if($case->ledgerEntries->count() > 0)
                            <div class="mt-6 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Date') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Type') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Description') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Recorded By') }}</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('Amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach($case->ledgerEntries as $entry)
                                            <tr class="transition hover:bg-gray-50">
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                    {{ $entry->created_at->format('M d, Y') }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                    @php
                                                        $ledgerBadge = $entry->type === 'trust'
                                                            ? 'bg-teal-100 text-teal-800'
                                                            : 'bg-orange-100 text-orange-800';
                                                    @endphp
                                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $ledgerBadge }}">
                                                        {{ __(ucfirst($entry->type)) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-700">
                                                    {{ $entry->description ?? '—' }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                                    {{ $entry->recordedBy->name ?? '—' }}
                                                </td>
                                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900">
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
        </div>
    </div>
</x-app-layout>
