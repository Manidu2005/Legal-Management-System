<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl">{{ __('Case Category Taxonomy') }}</h2>
            <a href="{{ route('case-categories.create', ['level' => 1]) }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('Add Main Type') }}
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert-success mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error mb-6">{{ session('error') }}</div>
    @endif

    <p class="mb-6 text-sm text-mist-500 dark:text-mist-400">
        {{ __('Main Type → Group → Specific Type. A case is always filed under a Specific Type (leaf). Deactivate a category instead of deleting it once it is in use.') }}
    </p>

    <div class="space-y-4">
        @forelse($mainTypes as $mainType)
            <div class="glass-card overflow-hidden" x-data="{ open: true }">
                <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-4">
                    <span class="font-semibold text-mist-950 dark:text-white">{{ $mainType->name }}</span>
                    <div class="flex items-center gap-3 text-xs">
                        <span class="text-mist-400 dark:text-mist-500">{{ __(':count groups', ['count' => $mainType->children->count()]) }}</span>
                        <a href="{{ route('case-categories.create', ['level' => 2, 'parent_id' => $mainType->id]) }}" @click.stop class="btn-secondary !py-1 !px-3 !text-xs">{{ __('Add Group') }}</a>
                        <a href="{{ route('case-categories.edit', $mainType) }}" @click.stop class="link-subtle underline">{{ __('Edit') }}</a>
                        <svg class="w-4 h-4 transition-transform text-mist-400" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </button>
                <div x-show="open" x-cloak class="border-t border-mist-950/10 dark:border-white/10 divide-y divide-mist-950/10 dark:divide-white/10">
                    @foreach($mainType->children as $group)
                        <div class="p-4" x-data="{ groupOpen: false }">
                            <button type="button" @click="groupOpen = !groupOpen" class="w-full flex items-center justify-between">
                                <span class="text-sm font-medium text-mist-800 dark:text-mist-200">{{ $group->name }}</span>
                                <div class="flex items-center gap-3 text-xs">
                                    <span class="text-mist-400 dark:text-mist-500">{{ __(':count types', ['count' => $group->children->count()]) }}</span>
                                    <a href="{{ route('case-categories.create', ['level' => 3, 'parent_id' => $group->id]) }}" @click.stop class="btn-secondary !py-1 !px-2 !text-xs">{{ __('Add Type') }}</a>
                                    <a href="{{ route('case-categories.edit', $group) }}" @click.stop class="link-subtle underline">{{ __('Edit') }}</a>
                                    <svg class="w-4 h-4 transition-transform text-mist-400" :class="groupOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>
                            <div x-show="groupOpen" x-cloak class="mt-3 overflow-x-auto">
                                <table class="table-dynamic">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Specific Type') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th class="text-right">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                                        @forelse($group->children as $leaf)
                                            <tr class="table-row-dynamic">
                                                <td class="text-sm text-mist-700 dark:text-mist-300">{{ $leaf->name }}</td>
                                                <td>
                                                    @if($leaf->is_active)
                                                        <span class="badge-dynamic bg-emerald-100 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-400 border-0">{{ __('Active') }}</span>
                                                    @else
                                                        <span class="badge-dynamic bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300 border-0">{{ __('Inactive') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-right text-sm">
                                                    <div class="flex items-center justify-end gap-3">
                                                        <a href="{{ route('case-categories.edit', $leaf) }}" class="link-subtle underline">{{ __('Edit') }}</a>
                                                        <form method="POST" action="{{ route('case-categories.toggle-active', $leaf) }}" class="inline">
                                                            @csrf @method('PATCH')
                                                            <button type="submit" class="link-subtle underline">{{ $leaf->is_active ? __('Deactivate') : __('Activate') }}</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('case-categories.destroy', $leaf) }}" class="inline" onsubmit="return confirm('{{ __('Delete this case type? This only works if no case uses it.') }}');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">{{ __('Delete') }}</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-4 py-6 text-center text-sm text-mist-400 dark:text-mist-500">{{ __('No specific types yet.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="glass-card p-12 text-center text-mist-500 dark:text-mist-400">
                {{ __('No case categories yet.') }}
            </div>
        @endforelse
    </div>
</x-app-layout>
