<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('court-dates.index') }}" class="text-gray-500 hover:text-gray-700 me-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Court Date') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('court-dates.update', $courtDate) }}">
                        @csrf
                        @method('PUT')

                        {{-- Case --}}
                        <div class="mb-6">
                            <x-input-label for="case_id" :value="__('Case')" />
                            <select id="case_id" name="case_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required>
                                <option value="">{{ __('— Select a case —') }}</option>
                                @foreach ($cases as $case)
                                    <option value="{{ $case->id }}" @selected(old('case_id', $courtDate->case_id) == $case->id)>
                                        {{ $case->display_name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('case_id')" class="mt-2" />
                        </div>

                        {{-- Date --}}
                        <div class="mb-6">
                            <x-input-label for="date" :value="__('Date & Time')" />
                            <x-text-input id="date" name="date" type="datetime-local"
                                class="mt-1 block w-full"
                                :value="old('date', $courtDate->date->format('Y-m-d\TH:i'))"
                                required />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>

                        {{-- Type --}}
                        <div class="mb-6">
                            <x-input-label for="type" :value="__('Court Date Type')" />
                            <select id="type" name="type"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required>
                                <option value="">{{ __('— Select type —') }}</option>
                                <option value="calling_date" @selected(old('type', $courtDate->type) === 'calling_date')>{{ __('Calling Date') }}</option>
                                <option value="trial_date"   @selected(old('type', $courtDate->type) === 'trial_date')>{{ __('Trial Date') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('court-dates.index') }}">
                                <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                            </a>
                            <x-primary-button>
                                <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                {{ __('Save Changes') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
