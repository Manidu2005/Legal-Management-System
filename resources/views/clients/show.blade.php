<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full flex-wrap gap-3">
            <div class="flex items-center gap-4">
                <a href="{{ route('clients.index') }}" class="link-subtle underline">
                    ← {{ __('Back to Clients') }}
                </a>
                <h2 class="heading-section !text-2xl">
                    {{ $client->name }}
                </h2>
            </div>
            <div>
                <a href="{{ route('clients.edit', $client) }}">
                    <x-secondary-button>
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        {{ __('Edit') }}
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert-success mb-6">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Client Detail Card --}}
    <div class="glass-card overflow-hidden mb-6">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row gap-6">
                @if($client->image_path)
                    <div class="shrink-0">
                        <img src="{{ asset('storage/' . $client->image_path) }}" alt="{{ $client->name }}" class="w-32 h-32 object-cover rounded-lg shadow-sm border border-mist-950/10 dark:border-white/10">
                    </div>
                @endif
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-mist-950 dark:text-white mb-4">{{ __('Client Information') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('NIC') }}</dt>
                            <dd class="mt-1 text-sm text-mist-950 dark:text-white font-mono font-semibold">{{ $client->nic }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('Full Name') }}</dt>
                            <dd class="mt-1 text-sm text-mist-950 dark:text-white">{{ $client->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('Phone') }}</dt>
                            <dd class="mt-1 text-sm text-mist-950 dark:text-white">{{ $client->phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('Email') }}</dt>
                            <dd class="mt-1 text-sm text-mist-950 dark:text-white">{{ $client->email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('Intake Date') }}</dt>
                            <dd class="mt-1 text-sm text-mist-950 dark:text-white">{{ $client->intake_date->format('d M Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-mist-500 dark:text-mist-400">{{ __('Total Cases') }}</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center rounded-full bg-mist-950/10 dark:bg-white/10 px-2.5 py-0.5 text-xs font-medium text-mist-900 dark:text-mist-100">
                                    {{ $client->cases->count() }}
                                </span>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cases Table --}}
    <div class="glass-card overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-mist-950 dark:text-white">{{ __('Cases') }}</h3>
                <a href="{{ route('cases.create', ['client_id' => $client->id]) }}">
                    <x-primary-button>
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('New Case for Client') }}
                    </x-primary-button>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="table-dynamic">
                    <thead>
                        <tr>
                            <th>{{ __('Case Name') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Assigned Attorney') }}</th>
                            <th class="text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                        @forelse ($client->cases as $case)
                            <tr class="table-row-dynamic">
                                <td class="font-medium text-mist-950 dark:text-white">
                                    {{ $case->display_name }}
                                </td>
                                <td class="text-mist-500 dark:text-mist-400">
                                    {{ $case->caseCategory->name ?? $case->case_type_other ?? '—' }}
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-800 dark:text-amber-400',
                                            'active' => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-400',
                                            'trial_scheduled' => 'bg-sky-100 dark:bg-sky-500/10 text-sky-800 dark:text-sky-400',
                                            'judgment_delivered' => 'bg-mist-950/10 dark:bg-white/10 text-mist-800 dark:text-mist-200',
                                            'case_closed' => 'bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300',
                                        ];
                                        $colorClass = $statusColors[$case->status] ?? 'bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300';
                                    @endphp
                                    <span class="badge-dynamic {{ $colorClass }} border-0">
                                        {{ __(str_replace('_', ' ', ucfirst($case->status))) }}
                                    </span>
                                </td>
                                <td class="text-mist-500 dark:text-mist-400">
                                    {{ $case->assignedAttorney->name ?? '—' }}
                                </td>
                                <td class="text-right font-medium">
                                    <a href="{{ route('cases.show', $case) }}" class="link-subtle">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-mist-500 dark:text-mist-400">
                                    <svg class="mx-auto h-12 w-12 text-mist-300 dark:text-mist-700 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    <p>{{ __('No cases found for this client.') }}</p>
                                    <p class="mt-1 text-mist-400 dark:text-mist-500">{{ __('Create a new case to get started.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
