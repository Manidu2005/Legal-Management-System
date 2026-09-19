<x-app-layout>
    <x-slot name="header">
        <h2 class="heading-display !text-3xl">{{ __('Judgment Library') }}</h2>
    </x-slot>

    @if(session('success'))
        <div class="alert-success mb-6">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert-error mb-6">{{ session('error') }}</div>
    @endif

    @if(session('info'))
        <div class="alert-info mb-6">{{ session('info') }}</div>
    @endif

    @if(($geminiKeyCount ?? 0) > 1)
        <p class="text-xs text-mist-500 dark:text-mist-400 mb-6">
            {{ __(':count Gemini API keys configured — indexing rotates to the next key automatically when one runs out of quota. Currently on :status.', ['count' => $geminiKeyCount, 'status' => $geminiKeyStatus]) }}
        </p>
    @endif

    @if(($openBatches ?? collect())->isNotEmpty())
        <div class="glass-card overflow-hidden mb-8 border-amber-200/80 dark:border-amber-500/20">
            <div class="p-4 sm:p-6 space-y-4">
                <h3 class="text-lg font-semibold text-mist-950 dark:text-white">{{ __('Open imports') }}</h3>
                <p class="text-sm text-mist-500 dark:text-mist-400">{{ __('Paused or awaiting review. Resume after Gemini quota resets — finished cases are never re-indexed.') }}</p>
                @foreach($openBatches as $batch)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-mist-950/10 dark:border-white/10 bg-white/70 dark:bg-white/5 p-4">
                        <div>
                            <div class="font-medium text-mist-950 dark:text-white">{{ $batch->original_filename ?: __('Import #:id', ['id' => $batch->id]) }}</div>
                            <div class="text-xs text-mist-500 dark:text-mist-400 mt-1">
                                {{ __(ucfirst(str_replace('_', ' ', $batch->status))) }}
                                — {{ __('Done') }}: {{ $batch->done_count }}
                                — {{ __('Remaining') }}: {{ $batch->pending_count }}
                            </div>
                            @if($batch->pause_reason)
                                <div class="text-xs text-amber-700 dark:text-amber-400 mt-1">{{ \Illuminate\Support\Str::limit($batch->pause_reason, 180) }}</div>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if($batch->status === 'awaiting_review')
                                <a href="{{ route('judgments.imports.review', $batch) }}" class="btn-primary !py-2 !px-3 !text-sm">{{ __('Review') }}</a>
                            @else
                                <form method="POST" action="{{ route('judgments.imports.resume', $batch) }}">
                                    @csrf
                                    <button type="submit" class="btn-primary !py-2 !px-3 !text-sm">{{ __('Resume') }}</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('judgments.imports.cancel', $batch) }}"
                                  onsubmit="return confirm('{{ __('Cancel this import? Indexed judgments are kept.') }}')">
                                @csrf
                                <button type="submit" class="btn-secondary !py-2 !px-3 !text-sm">{{ __('Cancel') }}</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(auth()->user()?->role === 'partner')
        <div class="glass-card overflow-hidden mb-8">
            <div class="p-4 sm:p-6 space-y-3">
                <h3 class="text-lg font-semibold text-mist-950 dark:text-white">{{ __('Public case-law datasets') }}</h3>
                <p class="text-sm text-mist-500 dark:text-mist-400">
                    {{ __('Import Sri Lanka Case Law (CLR/CLW/NLR/SLR) plus Court of Appeal and Supreme Court records. Gemini embeddings are stored so related-judgment search keeps working. Each click imports a batch; run again to continue. Hugging Face’s Supreme Court mirror is a small sample — the full Court of Appeal set is included.') }}
                </p>
                <p class="text-xs text-mist-500 dark:text-mist-400">
                    {{ __('In library: :imported. Waiting for embeddings: :pending.', [
                        'imported' => $datasetStatus['imported'] ?? 0,
                        'pending' => $datasetStatus['pending_embeddings'] ?? 0,
                    ]) }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('judgments.datasets.import') }}">
                        @csrf
                        <button type="submit" class="btn-primary !py-2 !px-3 !text-sm">{{ __('Import next batch') }}</button>
                    </form>
                    <form method="POST" action="{{ route('judgments.datasets.embed') }}">
                        @csrf
                        <button type="submit" class="btn-secondary !py-2 !px-3 !text-sm">{{ __('Embed pending') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Upload form --}}
    <div class="glass-card overflow-hidden animate-fade-in-up mb-8">
        <div class="p-6 border-b border-mist-950/10 dark:border-white/10">
            <h3 class="text-lg font-semibold text-mist-950 dark:text-white">{{ __('Upload Judgment') }}</h3>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ __('PDF and category are required. Title, court, and date are optional — Gemini will fill blanks after upload.') }}</p>
            <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">{{ __('Large law-report volumes can take several minutes to analyse. Keep this tab open until you see a result.') }}</p>
        </div>
        <form id="judgment-upload-form" method="POST" action="{{ route('judgments.store') }}" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <x-input-label for="title" :value="__('Title / Citation')" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                        :value="old('title')" placeholder="{{ __('e.g. Perera v. Silva') }}" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="category" :value="__('Category')" />
                    <select id="category" name="category" class="input-dynamic mt-1" required>
                        <option value="">{{ __('— Select —') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected(old('category') === $category)>
                                {{ str_replace('_', ' ', ucfirst($category)) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="court" :value="__('Court')" />
                    <x-text-input id="court" name="court" type="text" class="mt-1 block w-full"
                        :value="old('court')" placeholder="{{ __('Optional') }}" />
                    <x-input-error :messages="$errors->get('court')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="decided_date" :value="__('Decided Date')" />
                    <x-text-input id="decided_date" name="decided_date" type="date" class="mt-1 block w-full"
                        :value="old('decided_date')" />
                    <x-input-error :messages="$errors->get('decided_date')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="pdf" :value="__('PDF File')" />
                <input id="pdf" type="file" name="pdf" accept=".pdf,application/pdf"
                    class="mt-1 block w-full text-sm text-mist-500 dark:text-mist-400
                        file:me-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-mist-950/5 file:text-mist-800 dark:file:bg-white/10 dark:file:text-mist-200
                        hover:file:bg-mist-950/10 dark:hover:file:bg-white/10
                        cursor-pointer border border-mist-950/20 dark:border-white/20 rounded-md"
                    required />
                <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">{{ __('PDF only.') }} <span class="font-medium">{{ __('25MB max') }}</span>.</p>
                <x-input-error :messages="$errors->get('pdf')" class="mt-2" />
            </div>

            <div class="flex justify-end">
                <x-primary-button id="judgment-upload-submit" type="submit">
                    {{ __('Upload & Index') }}
                </x-primary-button>
            </div>
            <p id="judgment-upload-status" class="hidden text-sm text-amber-700 dark:text-amber-400 text-right">
                {{ __('Analysing PDF with Gemini… large volumes can take several minutes. Please keep this tab open.') }}
            </p>
        </form>
        <script>
            document.getElementById('judgment-upload-form')?.addEventListener('submit', function () {
                const status = document.getElementById('judgment-upload-status');
                const button = document.getElementById('judgment-upload-submit');
                if (status) status.classList.remove('hidden');
                if (button) {
                    button.disabled = true;
                    button.classList.add('opacity-60', 'cursor-wait');
                }
            });
        </script>
    </div>

    {{-- Listing --}}
    <div class="glass-card overflow-hidden animate-fade-in-up stagger-1">
        <div class="p-6 border-b border-mist-950/10 dark:border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 class="text-lg font-semibold text-mist-950 dark:text-white">{{ __('All Judgments') }}</h3>
            <form method="GET" action="{{ route('judgments.index') }}" class="flex flex-wrap gap-3">
                <input type="search" name="q" value="{{ $searchQuery ?? '' }}"
                    placeholder="{{ __('Search title, court, or summary') }}"
                    class="input-dynamic !py-2 !text-sm min-w-[220px]" />
                <select id="filter_category" name="category" class="input-dynamic !py-2 !text-sm">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected($selectedCategory === $category)>
                            {{ str_replace('_', ' ', ucfirst($category)) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">{{ __('Filter') }}</button>
                <a href="{{ route('judgments.index') }}" class="btn-secondary !py-2 !px-4 !text-sm">{{ __('Clear') }}</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="table-dynamic">
                <thead>
                    <tr>
                        <th>{{ __('Title') }}</th>
                        <th>{{ __('Court') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th class="text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                    @forelse($judgments as $judgment)
                        <tr class="table-row-dynamic">
                            <td>
                                <div class="font-medium text-mist-950 dark:text-white truncate max-w-[280px]">
                                    {{ $judgment->title ?: __('(Untitled)') }}
                                </div>
                                @if($judgment->sourceLabel())
                                    <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">{{ $judgment->sourceLabel() }}</p>
                                @endif
                                @if($judgment->summary)
                                    <p class="mt-1 text-xs text-mist-500 dark:text-mist-400 line-clamp-2 max-w-[360px]">{{ $judgment->summary }}</p>
                                @endif
                                @if(! empty($judgment->cited_acts))
                                    <p class="mt-1 text-xs text-mist-700/80 dark:text-mist-300/80 line-clamp-2 max-w-[360px]">
                                        {{ __('Cited:') }} {{ implode('; ', $judgment->cited_acts) }}
                                    </p>
                                @endif
                            </td>
                            <td>
                                <span class="text-sm text-mist-600 dark:text-mist-400">{{ $judgment->court ?: '—' }}</span>
                            </td>
                            <td>
                                <span class="text-mist-500 dark:text-mist-400 text-sm font-medium">
                                    {{ $judgment->decided_date?->format('d M Y') ?? '—' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $catClass = match($judgment->category) {
                                        'judgment' => 'bg-mist-950/10 dark:bg-white/10 text-mist-800 dark:text-mist-200 border-mist-950/10 dark:border-white/10',
                                        'act_or_ordinance' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-800 dark:text-amber-400 border-amber-200 dark:border-amber-500/20',
                                        default => 'bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300 border-mist-950/10 dark:border-white/10',
                                    };
                                @endphp
                                <span class="badge-dynamic border {{ $catClass }}">
                                    {{ str_replace('_', ' ', ucfirst($judgment->category)) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('judgments.download', $judgment) }}"
                                       class="p-1.5 text-mist-400 dark:text-mist-500 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-500/10 rounded-lg transition-colors"
                                       title="{{ __('Download') }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('judgments.destroy', $judgment) }}" class="inline"
                                          onsubmit="return confirm('{{ __('Delete this judgment? This cannot be undone.') }}');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 text-mist-400 dark:text-mist-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors"
                                                title="{{ __('Delete') }}">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <h3 class="text-lg font-medium text-mist-950 dark:text-white">{{ __('No judgments yet') }}</h3>
                                <p class="mt-1 text-mist-500 dark:text-mist-400">{{ __('Upload a PDF to start the shared reference library.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($judgments->hasPages())
            <div class="px-6 py-4 border-t border-mist-950/10 dark:border-white/10">
                {{ $judgments->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
