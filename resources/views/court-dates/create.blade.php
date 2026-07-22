<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('court-dates.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Schedule Court Date') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('court-dates.store') }}">
                        @csrf

                        {{-- Case Selection --}}
                        <div class="mb-6">
                            <x-input-label for="case_id" :value="__('Case')" />
                            <select id="case_id" name="case_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">{{ __('Select a case...') }}</option>
                                @foreach ($cases as $case)
                                    <option value="{{ $case->id }}" {{ old('case_id') == $case->id ? 'selected' : '' }}>
                                        Case #{{ $case->id }} — {{ $case->client->name ?? 'No Client' }}
                                        @if ($case->case_type)
                                            ({{ $case->case_type }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('case_id')" class="mt-2" />
                        </div>

                        {{-- Date and Time --}}
                        <div class="mb-6">
                            <x-input-label for="date" :value="__('Date & Time')" />
                            <x-text-input id="date" name="date" type="datetime-local"
                                          class="mt-1 block w-full"
                                          :value="old('date')"
                                          min="{{ now()->format('Y-m-d\TH:i') }}" />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>

                        {{-- Type Selection (Card-style Radio Buttons) --}}
                        <div class="mb-6">
                            <x-input-label :value="__('Court Date Type')" />
                            <p class="text-sm text-gray-500 mb-3">Select the type of court appearance</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Calling Date Card --}}
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="type" value="calling_date"
                                           class="peer sr-only"
                                           {{ old('type') === 'calling_date' ? 'checked' : '' }}>
                                    <div class="flex flex-col items-center p-6 rounded-lg border-2 border-gray-200
                                                peer-checked:border-blue-500 peer-checked:bg-blue-50
                                                hover:border-blue-300 transition-all duration-200">
                                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-3">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900">Calling Date</span>
                                        <span class="text-xs text-gray-500 mt-1 text-center">Regular court appearance for case management</span>
                                    </div>
                                    {{-- Checkmark indicator --}}
                                    <div class="absolute top-3 right-3 hidden peer-checked:block">
                                        <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </label>

                                {{-- Trial Date Card --}}
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="type" value="trial_date"
                                           class="peer sr-only"
                                           {{ old('type') === 'trial_date' ? 'checked' : '' }}>
                                    <div class="flex flex-col items-center p-6 rounded-lg border-2 border-gray-200
                                                peer-checked:border-red-500 peer-checked:bg-red-50
                                                hover:border-red-300 transition-all duration-200">
                                        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-3">
                                            <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900">Trial Date</span>
                                        <span class="text-xs text-gray-500 mt-1 text-center">Formal trial hearing — triggers billing & reminders</span>
                                    </div>
                                    {{-- Checkmark indicator --}}
                                    <div class="absolute top-3 right-3 hidden peer-checked:block">
                                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                            <a href="{{ route('court-dates.index') }}"
                               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button>
                                <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                {{ __('Schedule Court Date') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
