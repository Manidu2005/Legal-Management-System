<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Access Code Required') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-md sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>

                <h3 class="mt-4 text-center text-lg font-semibold text-gray-800">
                    {{ __('This case is protected') }}
                </h3>
                <p class="mt-2 text-center text-sm text-gray-500">
                    {{ __('Enter the access code given to you for :case to continue.', ['case' => $case->display_name]) }}
                </p>

                <form method="POST" action="{{ route('cases.access-code.store', $case) }}" class="mt-6">
                    @csrf

                    <x-input-label for="access_code" :value="__('Access Code')" class="sr-only" />
                    <x-text-input id="access_code" name="access_code" type="password"
                        class="block w-full text-center tracking-widest"
                        placeholder="{{ __('Enter access code') }}"
                        autocomplete="off" autofocus required />
                    <x-input-error :messages="$errors->get('access_code')" class="mt-2" />

                    <div class="mt-6">
                        <x-primary-button class="w-full justify-center">
                            {{ __('Unlock Case') }}
                        </x-primary-button>
                    </div>
                </form>

                <div class="mt-4 text-center">
                    <a href="{{ route('cases.index') }}" class="text-sm text-gray-500 underline hover:text-gray-700">
                        {{ __('Back to case list') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
