<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4 w-full">
            <a href="{{ route('billing.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
                ← Back to Billing
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create Invoice') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-gray-600 mb-6">
                        Select an active case below to generate a client-level financial report (invoice). This report will include trial dates, appearance fees, and all ledger entries (trust and operational).
                    </p>
                    
                    <form method="POST" action="{{ route('billing.generate-invoice') }}">
                        @csrf

                        <div class="mb-6">
                            <x-input-label for="case_id" :value="__('Select Case')" />
                            <select id="case_id" name="case_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="" disabled selected>{{ __('Select a case...') }}</option>
                                @foreach($cases as $case)
                                    <option value="{{ $case->id }}">
                                        LEX-{{ $case->created_at->format('Y') }}-{{ str_pad($case->id, 3, '0', STR_PAD_LEFT) }} — {{ $case->client->name ?? 'Unknown Client' }} ({{ $case->case_type ?? 'Unknown Type' }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('case_id')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('billing.index') }}">
                                <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                            </a>
                            <x-primary-button>
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                {{ __('Generate Invoice') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
