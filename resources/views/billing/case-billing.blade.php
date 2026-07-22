<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Case Billing') }} — #{{ $case->id }}
            </h2>
            <a href="{{ route('billing.report', $case->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                Generate Client Report
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Case Information --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Case Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Client</p>
                            <p class="text-base font-semibold text-gray-900">{{ $case->client->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Assigned Attorney</p>
                            <p class="text-base font-semibold text-gray-900">{{ $case->assignedAttorney->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Attorney Rate</p>
                            <p class="text-base font-semibold text-gray-900">LKR {{ number_format($attorneyRate, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Case Type</p>
                            <p class="text-base font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $case->case_type ?? '—')) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <p class="text-base font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $case->status)) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Appearance Fee Calculation --}}
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-white">
                    <h3 class="text-lg font-semibold mb-2">Appearance Fee Calculation</h3>
                    <p class="text-3xl font-bold">
                        {{ $trialDateCount }} trial date{{ $trialDateCount !== 1 ? 's' : '' }}
                        &times; LKR {{ number_format($attorneyRate, 2) }}
                        = <span class="text-yellow-200">LKR {{ number_format($summary['appearance_fee'], 2) }}</span>
                    </p>
                </div>
            </div>

            {{-- Balance Summary --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                                <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-gray-500">Trust Balance</p>
                                <p class="text-2xl font-bold {{ $summary['balances']['trust'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    LKR {{ number_format($summary['balances']['trust'], 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-emerald-100 rounded-lg p-3">
                                <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <div class="ms-4">
                                <p class="text-sm font-medium text-gray-500">Operational Balance</p>
                                <p class="text-2xl font-bold {{ $summary['balances']['operational'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    LKR {{ number_format($summary['balances']['operational'], 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Add Ledger Entry Form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Ledger Entry</h3>

                    <form method="POST" action="{{ route('ledger-entries.store') }}">
                        @csrf
                        <input type="hidden" name="case_id" value="{{ $case->id }}">

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            {{-- Type --}}
                            <div>
                                <x-input-label for="type" :value="__('Ledger Type')" />
                                <div class="mt-2 flex items-center space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="type" value="trust" class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('type', 'trust') === 'trust' ? 'checked' : '' }}>
                                        <span class="ms-2 text-sm text-gray-700">Trust</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="type" value="operational" class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('type') === 'operational' ? 'checked' : '' }}>
                                        <span class="ms-2 text-sm text-gray-700">Operational</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('type')" class="mt-1" />
                            </div>

                            {{-- Amount --}}
                            <div>
                                <x-input-label for="amount" :value="__('Amount (LKR)')" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount')" placeholder="0.00" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                            </div>

                            {{-- Description --}}
                            <div>
                                <x-input-label for="description" :value="__('Description')" />
                                <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" placeholder="e.g. Court filing fee" required maxlength="500" />
                                <x-input-error :messages="$errors->get('description')" class="mt-1" />
                            </div>

                            {{-- Submit --}}
                            <div>
                                <x-primary-button class="w-full justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    {{ __('Add Entry') }}
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Trust and Operational Ledger Tables --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Trust Ledger --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Trust Ledger</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $summary['balances']['trust'] >= 0 ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' }}">
                                LKR {{ number_format($summary['balances']['trust'], 2) }}
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse ($trustEntries as $entry)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                {{ $entry->created_at->format('d M Y') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ $entry->description }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-right text-emerald-600">
                                                LKR {{ number_format($entry->amount, 2) }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                                <form method="POST" action="{{ route('ledger-entries.destroy', $entry->id) }}" class="inline" onsubmit="return confirm('Delete this ledger entry?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 transition-colors duration-150">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">
                                                No trust ledger entries yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Operational Ledger --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Operational Ledger</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $summary['balances']['operational'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                LKR {{ number_format($summary['balances']['operational'], 2) }}
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse ($operationalEntries as $entry)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                {{ $entry->created_at->format('d M Y') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ $entry->description }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-right text-emerald-600">
                                                LKR {{ number_format($entry->amount, 2) }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                                <form method="POST" action="{{ route('ledger-entries.destroy', $entry->id) }}" class="inline" onsubmit="return confirm('Delete this ledger entry?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 transition-colors duration-150">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">
                                                No operational ledger entries yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
