<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4 w-full">
            <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
                ← Back to Users
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create User') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        {{-- Name --}}
                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Full Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        {{-- Email --}}
                        <div class="mb-4">
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        {{-- Password --}}
                        <div class="mb-4">
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        {{-- Confirm Password --}}
                        <div class="mb-4">
                            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        {{-- Role --}}
                        <div class="mb-4">
                            <x-input-label for="role" :value="__('Role')" />
                            <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Select a role</option>
                                <option value="partner" {{ old('role') === 'partner' ? 'selected' : '' }}>Partner</option>
                                <option value="associate" {{ old('role') === 'associate' ? 'selected' : '' }}>Associate</option>
                                <option value="clerk" {{ old('role') === 'clerk' ? 'selected' : '' }}>Clerk</option>
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>

                        {{-- Branch --}}
                        <div class="mb-4">
                            <x-input-label for="branch" :value="__('Branch')" />
                            <x-text-input id="branch" name="branch" type="text" class="mt-1 block w-full" :value="old('branch')" required placeholder="e.g. Colombo Main, Kandy" />
                            <x-input-error :messages="$errors->get('branch')" class="mt-2" />
                        </div>

                        {{-- Flat Appearance Rate --}}
                        <div class="mb-6">
                            <x-input-label for="flat_appearance_rate" :value="__('Flat Appearance Rate')" />
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">LKR</span>
                                </div>
                                <x-text-input id="flat_appearance_rate" name="flat_appearance_rate" type="number" step="0.01" min="0" class="block w-full pl-12" :value="old('flat_appearance_rate')" placeholder="0.00" />
                            </div>
                            <x-input-error :messages="$errors->get('flat_appearance_rate')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('users.index') }}">
                                <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                            </a>
                            <x-primary-button>{{ __('Create User') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
