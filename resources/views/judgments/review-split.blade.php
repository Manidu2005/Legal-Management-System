<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl">{{ __('Review Detected Judgments') }}</h2>
            <a href="{{ route('judgments.index') }}" class="btn-secondary">{{ __('Back to Library') }}</a>
        </div>
    </x-slot>

    @if(session('info'))
        <div class="alert-info mb-6">{{ session('info') }}</div>
    @endif

    @if(session('error'))
        <div class="alert-error mb-6">{{ session('error') }}</div>
    @endif

    <div class="glass-card overflow-hidden mb-6">
        <div class="p-6 border-b border-mist-950/10 dark:border-white/10">
            <h3 class="text-lg font-semibold text-mist-950 dark:text-white">{{ __('Confirm page ranges before indexing') }}</h3>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                {{ __('Uncheck wrongly detected entries or adjust page ranges. Already-indexed pages from this same PDF are skipped automatically (no duplicate, no extra Gemini calls).') }}
            </p>
            <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                {{ __('File:') }} {{ $batch->original_filename ?: basename($batch->source_pdf_path) }}
                — {{ $batch->items->count() }} {{ __('detected') }}
            </p>
        </div>

        <form method="POST" action="{{ route('judgments.imports.confirm', $batch) }}" class="p-6 space-y-6" id="confirm-import-form">
            @csrf

            @foreach($batch->items as $item)
                @php $index = $item->position; @endphp
                <div class="rounded-xl border border-mist-950/10 dark:border-white/10 bg-white/80 dark:bg-white/5 p-4 space-y-4 {{ $item->status === 'skipped' ? 'opacity-70' : '' }}">
                    <div class="flex items-center justify-between gap-3">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-mist-950 dark:text-white">
                            <input type="checkbox" name="cases[{{ $index }}][include]" value="1"
                                   @checked(old('cases.'.$index.'.include', $item->include && $item->status !== 'skipped'))
                                   @disabled($item->status === 'skipped' && $item->judgment_id)
                                   class="rounded border-mist-950/20 dark:border-white/20 text-mist-950 dark:text-white focus:ring-mist-500/20">
                            {{ __('Include case #:num', ['num' => $index + 1]) }}
                            @if($item->status === 'skipped' && $item->judgment_id)
                                <span class="ml-2 text-xs font-medium text-emerald-700 dark:text-emerald-400">{{ __('Already indexed — will skip') }}</span>
                            @endif
                        </label>
                        <span class="text-xs text-mist-500 dark:text-mist-400">
                            {{ __('Pages') }} {{ $item->start_page }}–{{ $item->end_page }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label :value="__('Citation')" />
                            <x-text-input type="text" name="cases[{{ $index }}][citation]" class="mt-1 block w-full"
                                          :value="old('cases.'.$index.'.citation', $item->citation)" />
                        </div>
                        <div>
                            <x-input-label :value="__('Court')" />
                            <x-text-input type="text" name="cases[{{ $index }}][court]" class="mt-1 block w-full"
                                          :value="old('cases.'.$index.'.court', $item->court)" />
                        </div>
                        <div>
                            <x-input-label :value="__('Decided date')" />
                            <x-text-input type="date" name="cases[{{ $index }}][decided_date]" class="mt-1 block w-full"
                                          :value="old('cases.'.$index.'.decided_date', optional($item->decided_date)->format('Y-m-d'))" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label :value="__('Start page')" />
                                <x-text-input type="number" min="1" name="cases[{{ $index }}][start_page]" class="mt-1 block w-full"
                                              :value="old('cases.'.$index.'.start_page', $item->start_page)" required />
                            </div>
                            <div>
                                <x-input-label :value="__('End page')" />
                                <x-text-input type="number" min="1" name="cases[{{ $index }}][end_page]" class="mt-1 block w-full"
                                              :value="old('cases.'.$index.'.end_page', $item->end_page)" required />
                            </div>
                        </div>
                    </div>

                    <div>
                        <x-input-label :value="__('Cited acts (one per line)')" />
                        <textarea name="cases[{{ $index }}][cited_acts]" rows="3" class="input-dynamic mt-1 !text-sm">{{ old('cases.'.$index.'.cited_acts', implode("\n", $item->cited_acts ?? [])) }}</textarea>
                    </div>
                </div>
            @endforeach

            <div class="flex flex-wrap items-center justify-end gap-3">
                <button type="submit" formaction="{{ route('judgments.imports.cancel', $batch) }}"
                        class="btn-secondary"
                        onclick="return confirm('{{ __('Cancel this import? Already-indexed judgments are kept.') }}')">
                    {{ __('Cancel import') }}
                </button>
                <x-primary-button id="confirm-split-submit" type="submit">
                    {{ __('Confirm & index (until quota)') }}
                </x-primary-button>
            </div>
            <p id="confirm-split-status" class="hidden text-sm text-amber-700 dark:text-amber-400 text-right">
                {{ __('Indexing… will pause automatically if Gemini quota runs out. Resume later from the Judgment Library.') }}
            </p>
        </form>
        <script>
            (function () {
                const form = document.getElementById('confirm-import-form');
                form?.addEventListener('submit', function (e) {
                    const submitter = e.submitter;
                    if (submitter && submitter.getAttribute('formaction')) {
                        return;
                    }
                    document.getElementById('confirm-split-status')?.classList.remove('hidden');
                    const button = document.getElementById('confirm-split-submit');
                    if (button) {
                        button.disabled = true;
                        button.classList.add('opacity-60', 'cursor-wait');
                    }
                });
            })();
        </script>
    </div>
</x-app-layout>
