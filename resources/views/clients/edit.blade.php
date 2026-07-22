<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Client') }} — {{ $client->name }}
            </h2>
            <a href="{{ route('clients.show', $client) }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
                ← Back to Client
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('clients.update', $client) }}">
                        @csrf
                        @method('PUT')

                        {{-- NIC - Readonly --}}
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <x-input-label for="nic" class="text-base font-semibold text-gray-700">
                                {{ __('National Identity Card (NIC)') }}
                            </x-input-label>
                            <p class="text-xs text-gray-500 mb-2">NIC cannot be changed after registration.</p>
                            <x-text-input id="nic" name="nic" type="text" class="mt-1 block w-full text-lg font-mono bg-gray-100 cursor-not-allowed" :value="$client->nic" readonly />
                        </div>

                        {{-- Name --}}
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Full Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $client->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        {{-- Phone --}}
                        <div class="mb-4">
                            <x-input-label for="phone" :value="__('Phone Number')" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $client->phone)" placeholder="+94 XX XXX XXXX" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        {{-- Email --}}
                        <div class="mb-4">
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $client->email)" placeholder="Optional" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        {{-- Intake Date --}}
                        <div class="mb-6">
                            <x-input-label for="intake_date" :value="__('Intake Date')" />
                            <x-text-input id="intake_date" name="intake_date" type="date" class="mt-1 block w-full" :value="old('intake_date', $client->intake_date->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('intake_date')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('clients.show', $client) }}">
                                <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                            </a>
                            <x-primary-button>{{ __('Update Client') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
