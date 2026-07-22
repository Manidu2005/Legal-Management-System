<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Documents') }}
            </h2>
            <a href="{{ route('documents.create') }}">
                <x-primary-button>
                    <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    {{ __('Upload Document') }}
                </x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Success Message --}}
            @if (session('success'))
                <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filters --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('documents.index') }}" class="flex flex-col sm:flex-row gap-4 items-end">
                        <div class="w-full sm:w-auto sm:min-w-[200px]">
                            <x-input-label for="category" :value="__('Category')" />
                            <select id="category" name="category"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">All Categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($selectedCategory === $category)>
                                        {{ ucfirst($category) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-full sm:w-auto sm:min-w-[280px]">
                            <x-input-label for="case_id" :value="__('Case')" />
                            <select id="case_id" name="case_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">All Cases</option>
                                @foreach ($cases as $case)
                                    <option value="{{ $case->id }}" @selected($selectedCaseId == $case->id)>
                                        Case #{{ $case->id }} — {{ $case->client->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                            <a href="{{ route('documents.index') }}">
                                <x-secondary-button type="button">{{ __('Clear') }}</x-secondary-button>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Documents Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($documents->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900">No documents found</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by uploading a new document.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($documents as $document)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $document->id }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @if ($document->legalCase)
                                                    <span class="text-indigo-600 font-medium">
                                                        Case #{{ $document->legalCase->id }}
                                                    </span>
                                                    <span class="text-gray-500 block text-xs">{{ $document->legalCase->client->name ?? '' }}</span>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @php
                                                    $badgeColors = [
                                                        'evidence' => 'bg-blue-100 text-blue-800',
                                                        'deeds' => 'bg-emerald-100 text-emerald-800',
                                                        'correspondence' => 'bg-amber-100 text-amber-800',
                                                    ];
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColors[$document->category] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ ucfirst($document->category) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 uppercase">{{ $document->file_type }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $document->uploader->name ?? 'Unknown' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $document->created_at->format('d M Y') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                                <a href="{{ route('documents.show', $document) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                                <a href="{{ Storage::disk('public')->url($document->file_path) }}" download class="text-green-600 hover:text-green-900">Download</a>
                                                <form method="POST" action="{{ route('documents.destroy', $document) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this document?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6">
                            {{ $documents->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
