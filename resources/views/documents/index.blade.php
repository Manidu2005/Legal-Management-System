<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl text-slate-800">Document Vault</h2>
            <a href="{{ route('documents.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Upload Document
            </a>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="glass border border-emerald-500/30 bg-emerald-50/80 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3 animate-fade-in-up">
            <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="glass-card overflow-hidden animate-fade-in-up stagger-1">
        <div class="p-6 border-b border-slate-200/60 bg-white/50 backdrop-blur-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <form method="GET" action="{{ route('documents.index') }}" class="flex flex-wrap gap-4 w-full sm:w-auto">
                <div>
                    <label for="category" class="sr-only">Category</label>
                    <select id="category" name="category" class="input-dynamic !py-2 !text-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected($selectedCategory === $category)>
                                {{ ucfirst($category) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="case_id" class="sr-only">Case</label>
                    <select id="case_id" name="case_id" class="input-dynamic !py-2 !text-sm">
                        <option value="">All Cases</option>
                        @foreach($cases as $case)
                            <option value="{{ $case->id }}" @selected($selectedCaseId == $case->id)>
                                LEX-{{ $case->created_at->format('Y') }}-{{ str_pad($case->id, 3, '0', STR_PAD_LEFT) }} — {{ Str::limit($case->title, 20) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary !py-2 !px-4 !text-sm">Filter</button>
                    <a href="{{ route('documents.index') }}" class="btn-secondary !py-2 !px-4 !text-sm">Clear</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="table-dynamic">
                <thead>
                    <tr>
                        <th>Case Reference</th>
                        <th>Category</th>
                        <th>File Type</th>
                        <th>Uploaded By</th>
                        <th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $document)
                        <tr class="table-row-dynamic group">
                            <td>
                                @if($document->legalCase)
                                    <div class="flex items-center gap-2 font-mono text-xs font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-100 w-fit">
                                        LEX-{{ $document->legalCase->created_at->format('Y') }}-{{ str_pad($document->legalCase->id, 3, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1 font-medium truncate max-w-[200px]">
                                        {{ $document->legalCase->title ?? '' }}
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $catClass = match($document->category) {
                                        'evidence' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'deeds' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'correspondence' => 'bg-sky-100 text-sky-700 border-sky-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="badge-dynamic border {{ $catClass }}">{{ ucfirst($document->category) }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                        {{ $document->file_type }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 text-xs font-bold">
                                        {{ substr($document->uploader->name ?? '?', 0, 1) }}
                                    </div>
                                    <span class="text-slate-600 text-sm">{{ $document->uploader->name ?? 'Unknown' }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="text-slate-500 text-sm font-medium">{{ $document->created_at->format('d M Y') }}</span>
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <a href="{{ route('documents.show', $document) }}" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="View Details">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ Storage::disk('public')->url($document->file_path) }}" download class="p-1.5 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition-colors" title="Download">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('documents.destroy', $document) }}" class="inline" onsubmit="return confirm('Delete this document? This cannot be undone.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
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
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-4">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-slate-900">No documents found</h3>
                                <p class="mt-1 text-slate-500">Upload your first document to get started.</p>
                                <div class="mt-6">
                                    <a href="{{ route('documents.create') }}" class="btn-primary">
                                        Upload Document
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($documents) && $documents->hasPages())
            <div class="px-6 py-4 border-t border-slate-200/60 bg-slate-50/50">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
