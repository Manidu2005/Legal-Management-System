<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4 w-full">
            <a href="{{ route('clients.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
                ← Back to Clients
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Client Intake — New Registration') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('clients.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- NIC - Prominent Primary Field --}}
                        <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                            <x-input-label for="nic" class="text-base font-semibold text-indigo-900">
                                {{ __('National Identity Card (NIC)') }} <span class="text-red-500">*</span>
                            </x-input-label>
                            <p class="text-xs text-indigo-600 mb-2">This is the primary identifier for the client and must be unique.</p>
                            <x-text-input id="nic" name="nic" type="text" class="mt-1 block w-full text-lg font-mono border-indigo-300 focus:border-indigo-500 focus:ring-indigo-500" :value="old('nic')" required autofocus placeholder="e.g. 200012345678 or 901234567V" />
                            <x-input-error :messages="$errors->get('nic')" class="mt-2" />
                        </div>

                        {{-- Name --}}
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Full Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        {{-- Phone --}}
                        <div class="mb-4">
                            <x-input-label for="phone" :value="__('Phone Number')" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" placeholder="+94 XX XXX XXXX" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        {{-- Email --}}
                        <div class="mb-4">
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" placeholder="Optional" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        {{-- Client Image (Optional) --}}
                        <div class="mb-6">
                            <x-input-label for="image" :value="__('Client Image (Optional)')" />
                            <input id="image" name="image" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-indigo-50 file:text-indigo-700
                                hover:file:bg-indigo-100" />
                            <x-input-error :messages="$errors->get('image')" class="mt-2" />
                        </div>


                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('clients.index') }}">
                                <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                            </a>
                            <x-primary-button>{{ __('Register Client') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
