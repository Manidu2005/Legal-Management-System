<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl">{{ __('Document Vault') }}</h2>
            <a href="{{ route('documents.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                {{ __('Upload Document') }}
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert-success mb-6">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="glass-card overflow-hidden animate-fade-in-up stagger-1">
        <div class="p-6 border-b border-mist-950/10 dark:border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <form method="GET" action="{{ route('documents.index') }}" class="flex flex-wrap gap-4 w-full sm:w-auto">
                <div>
                    <label for="category" class="sr-only">{{ __('Category') }}</label>
                    <select id="category" name="category" class="input-dynamic !py-2 !text-sm">
                        <option value="">{{ __('All Categories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected($selectedCategory === $category)>
                                {{ ucfirst($category) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="case_id" class="sr-only">{{ __('Case') }}</label>
                    <select id="case_id" name="case_id" class="input-dynamic !py-2 !text-sm">
                        <option value="">{{ __('All Cases') }}</option>
                        @foreach($cases as $case)
                            <option value="{{ $case->id }}" @selected($selectedCaseId == $case->id)>
                                {{ $case->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">{{ __('Filter') }}</button>
                    <a href="{{ route('documents.index') }}" class="btn-secondary !py-2 !px-4 !text-sm">{{ __('Clear') }}</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="table-dynamic">
                <thead>
                    <tr>
                        <th>{{ __('Document Name') }}</th>
                        <th>{{ __('Case') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Client Name') }}</th>
                        <th>{{ __('Uploaded By') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th class="text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                    @forelse($documents as $document)
                        <tr class="table-row-dynamic group">
                            <td>
                                <div class="font-medium text-mist-950 dark:text-white truncate max-w-[240px]">
                                    {{ $document->display_name }}
                                </div>
                            </td>
                            <td>
                                @if($document->legalCase)
                                    <div class="text-sm text-mist-600 dark:text-mist-400 truncate max-w-[200px]">
                                        {{ $document->legalCase->display_name }}
                                    </div>
                                @else
                                    <span class="text-mist-400 dark:text-mist-500 text-sm">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $catClass = match($document->category) {
                                        'evidence' => 'bg-amber-100 dark:bg-amber-500/10 text-amber-800 dark:text-amber-400 border-amber-200 dark:border-amber-500/20',
                                        'deeds' => 'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
                                        'correspondence' => 'bg-sky-100 dark:bg-sky-500/10 text-sky-800 dark:text-sky-400 border-sky-200 dark:border-sky-500/20',
                                        default => 'bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300 border-mist-950/10 dark:border-white/10',
                                    };
                                @endphp
                                <span class="badge-dynamic border {{ $catClass }}">{{ ucfirst($document->category) }}</span>
                            </td>
                            <td>
                                <div class="text-sm font-medium text-mist-950 dark:text-white truncate max-w-[200px]">
                                    {{ $document->legalCase->client->name ?? __('Unknown Client') }}
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-mist-950/10 dark:bg-white/10 flex items-center justify-center text-mist-700 dark:text-mist-300 text-xs font-bold">
                                        {{ substr($document->uploader->name ?? '?', 0, 1) }}
                                    </div>
                                    <span class="text-mist-600 dark:text-mist-400 text-sm">{{ $document->uploader->name ?? __('Unknown') }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-mist-500 dark:text-mist-400 text-sm font-medium">{{ $document->created_at->format('d M Y') }}</span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2 transition-opacity duration-200">
                                    <a href="{{ route('documents.show', $document) }}" class="p-1.5 text-mist-400 dark:text-mist-500 hover:text-mist-950 dark:hover:text-white hover:bg-mist-950/10 dark:hover:bg-white/10 rounded-lg transition-colors" title="{{ __('View Details') }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ Storage::disk('public')->url($document->file_path) }}" download class="p-1.5 text-mist-400 dark:text-mist-500 hover:text-sky-600 dark:hover:text-sky-400 hover:bg-sky-50 dark:hover:bg-sky-500/10 rounded-lg transition-colors" title="{{ __('Download') }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('documents.destroy', $document) }}" class="inline" onsubmit="return confirm('{{ __('Delete this document? This cannot be undone.') }}');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-mist-400 dark:text-mist-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors" title="{{ __('Delete') }}">
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
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-mist-950/5 dark:bg-white/5 flex items-center justify-center text-mist-400 dark:text-mist-500 mx-auto mb-4">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-mist-950 dark:text-white">{{ __('No documents found') }}</h3>
                                <p class="mt-1 text-mist-500 dark:text-mist-400">{{ __('Upload your first document to get started.') }}</p>
                                <div class="mt-6">
                                    <a href="{{ route('documents.create') }}" class="btn-primary">
                                        {{ __('Upload Document') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($documents) && $documents->hasPages())
            <div class="px-6 py-4 border-t border-mist-950/10 dark:border-white/10">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
