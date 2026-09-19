<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('courts.index') }}" class="text-mist-400 dark:text-mist-500 hover:text-mist-950 dark:hover:text-white transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h2 class="heading-section !text-2xl">{{ __('Add Court') }}</h2>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="glass-card overflow-hidden">
            <form method="POST" action="{{ route('courts.store') }}" class="p-6 sm:p-8">
                @csrf

                <div class="space-y-6">
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus placeholder="{{ __('e.g. District Court') }}" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tier" :value="__('Tier')" />
                        <select id="tier" name="tier" class="input-dynamic mt-1" required>
                            <option value="">{{ __('Select...') }}</option>
                            @foreach(\App\Models\Court::TIERS as $tierValue => $tierLabel)
                                <option value="{{ $tierValue }}" @selected(old('tier') === $tierValue)>{{ $tierLabel }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('tier')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="sort_order" :value="__('Sort Order')" />
                        <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="old('sort_order', 0)" />
                        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('Description (optional)')" />
                        <textarea id="description" name="description" rows="2" class="input-dynamic mt-1">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-mist-300 dark:border-white/20">
                        <label for="is_active" class="text-sm text-mist-700 dark:text-mist-300">{{ __('Active') }}</label>
                    </div>
                </div>

                <div class="mt-8 flex items-center justify-end gap-4 border-t border-mist-950/10 dark:border-white/10 pt-6">
                    <a href="{{ route('courts.index') }}">
                        <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                    </a>
                    <x-primary-button>{{ __('Create Court') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
