<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3 w-full">
            <a href="{{ route('billing.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors shrink-0" title="{{ __('Back to Billing') }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h2 class="heading-display !text-2xl sm:!text-3xl text-slate-800">{{ __('Create Invoice') }}</h2>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto animate-fade-in-up stagger-1">
        <div class="glass-card overflow-hidden">
            <div class="p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm">
                <h3 class="heading-section !text-lg">{{ __('Client Financial Report') }}</h3>
                <p class="text-sm text-slate-500 mt-1">
                    {{ __('Select an active case below to generate a client-level financial report (invoice). This report will include trial dates, appearance fees, and all ledger entries (trust and operational).') }}
                </p>
            </div>

            <div class="p-6">
                <form method="POST" action="{{ route('billing.generate-invoice') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="case_id" class="label-dynamic">{{ __('Select Case') }}</label>
                        <select id="case_id" name="case_id" class="input-dynamic" required>
                            <option value="" disabled {{ old('case_id') ? '' : 'selected' }}>{{ __('Select a case...') }}</option>
                            @forelse($cases as $case)
                                <option value="{{ $case->id }}" @selected(old('case_id') == $case->id)>
                                    {{ $case->display_name }}
                                </option>
                            @empty
                                <option value="" disabled>{{ __('No active cases available') }}</option>
                            @endforelse
                        </select>
                        <x-input-error :messages="$errors->get('case_id')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('billing.index') }}" class="btn-secondary !py-2.5 !px-5 !text-sm">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="btn-primary !py-2.5 !px-5 !text-sm" @disabled($cases->isEmpty())>
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            {{ __('Generate Invoice') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
