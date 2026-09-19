<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('case-categories.index') }}" class="text-mist-400 dark:text-mist-500 hover:text-mist-950 dark:hover:text-white transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h2 class="heading-section !text-2xl">{{ __('Edit Case Category') }} — {{ $caseCategory->name }}</h2>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="glass-card overflow-hidden">
            <form method="POST" action="{{ route('case-categories.update', $caseCategory) }}" class="p-6 sm:p-8"
                  x-data="{ level: '{{ old('level', $caseCategory->level) }}' }">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <x-input-label for="level" :value="__('Level')" />
                        <select id="level" name="level" x-model="level" class="input-dynamic mt-1" required>
                            <option value="1" @selected(old('level', $caseCategory->level) == 1)>{{ __('Main Type (top level)') }}</option>
                            <option value="2" @selected(old('level', $caseCategory->level) == 2)>{{ __('Group (under a Main Type)') }}</option>
                            <option value="3" @selected(old('level', $caseCategory->level) == 3)>{{ __('Specific Type (leaf)') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('level')" class="mt-2" />
                    </div>

                    <div x-show="level === '2'" x-cloak>
                        <x-input-label for="parent_id_main" :value="__('Parent Main Type')" />
                        <select id="parent_id_main" name="parent_id" class="input-dynamic mt-1" :disabled="level !== '2'">
                            <option value="">{{ __('Select...') }}</option>
                            @foreach($mainTypes as $mainType)
                                <option value="{{ $mainType->id }}" @selected(old('parent_id', $caseCategory->parent_id) == $mainType->id)>{{ $mainType->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="level === '3'" x-cloak>
                        <x-input-label for="parent_id_group" :value="__('Parent Group')" />
                        <select id="parent_id_group" name="parent_id" class="input-dynamic mt-1" :disabled="level !== '3'">
                            <option value="">{{ __('Select...') }}</option>
                            @foreach($groups->groupBy(fn($g) => $g->parent->name ?? '—') as $mainName => $groupOptions)
                                <optgroup label="{{ $mainName }}">
                                    @foreach($groupOptions as $groupOption)
                                        <option value="{{ $groupOption->id }}" @selected(old('parent_id', $caseCategory->parent_id) == $groupOption->id)>{{ $groupOption->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $caseCategory->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="sort_order" :value="__('Sort Order')" />
                        <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="old('sort_order', $caseCategory->sort_order)" />
                        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description (optional)')" />
                        <textarea id="description" name="description" rows="2" class="input-dynamic mt-1">{{ old('description', $caseCategory->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $caseCategory->is_active)) class="rounded border-mist-300 dark:border-white/20">
                        <label for="is_active" class="text-sm text-mist-700 dark:text-mist-300">{{ __('Active') }}</label>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-4 border-t border-mist-950/10 dark:border-white/10 pt-6">
                    <a href="{{ route('case-categories.index') }}">
                        <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                    </a>
                    <x-primary-button>{{ __('Update Category') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
