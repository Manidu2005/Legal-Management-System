<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl">{{ __('Courts & Forums') }}</h2>
            <a href="{{ route('courts.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('Add Court') }}
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert-success mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error mb-6">{{ session('error') }}</div>
    @endif

    <div class="space-y-6">
        @forelse($courts as $tier => $tierCourts)
            <div class="glass-card overflow-hidden">
                <div class="border-b border-mist-950/10 dark:border-white/10 px-6 py-4">
                    <h3 class="text-sm font-semibold uppercase tracking-widest text-mist-800 dark:text-mist-200">
                        {{ \App\Models\Court::TIERS[$tier] ?? $tier }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="table-dynamic">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                            @foreach($tierCourts as $court)
                                <tr class="table-row-dynamic">
                                    <td class="text-sm font-medium text-mist-950 dark:text-white">{{ $court->name }}</td>
                                    <td>
                                        @if($court->is_active)
                                            <span class="badge-dynamic bg-emerald-100 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-400 border-0">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge-dynamic bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300 border-0">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-sm">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('courts.edit', $court) }}" class="link-subtle underline">{{ __('Edit') }}</a>
                                            <form method="POST" action="{{ route('courts.toggle-active', $court) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="link-subtle underline">{{ $court->is_active ? __('Deactivate') : __('Activate') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('courts.destroy', $court) }}" class="inline" onsubmit="return confirm('{{ __('Delete this court? This only works if no case uses it.') }}');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="glass-card p-12 text-center text-mist-500 dark:text-mist-400">
                {{ __('No courts configured yet.') }}
            </div>
        @endforelse
    </div>
</x-app-layout>
