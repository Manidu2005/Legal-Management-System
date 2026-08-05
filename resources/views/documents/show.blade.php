<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('documents.index') }}" class="text-gray-500 hover:text-gray-700 me-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Document Details') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            {{-- Success Message --}}
            @if (session('success'))
                <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700 border border-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700 border border-red-200">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Document Info Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">File Information</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Document ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900">#{{ $document->id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">File Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ basename($document->file_path) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">File Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900 uppercase">{{ $document->file_type }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Category</dt>
                                    <dd class="mt-1">
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
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Information</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Case</dt>
                                    <dd class="mt-1 text-sm">
                                        @if ($document->legalCase)
                                            <span class="text-indigo-600 font-medium">
                                                Case #{{ $document->legalCase->id }}
                                            </span>
                                            @if ($document->legalCase->client)
                                                <span class="text-gray-500"> — {{ $document->legalCase->client->name }}</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Uploaded By</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $document->uploader->name ?? 'Unknown' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Upload Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $document->created_at->format('d F Y, h:i A') }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="mt-6 pt-6 border-t border-gray-200 flex items-center gap-3">
                        <a href="{{ route('documents.download', $document) }}">
                            <x-primary-button type="button">
                                <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                {{ __('Download') }}
                            </x-primary-button>
                        </a>

                        <x-danger-button
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'confirm-document-deletion')"
                        >
                            <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            {{ __('Delete') }}
                        </x-danger-button>
                    </div>
                </div>
            </div>

            {{-- PDF Preview --}}
            @if (strtolower($document->file_type) === 'pdf')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Document Preview</h3>
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <iframe
                                src="{{ Storage::disk('public')->url($document->file_path) }}"
                                class="w-full"
                                style="height: 700px;"
                                title="Document Preview"
                            ></iframe>
                        </div>
                    </div>
                </div>
            @elseif (in_array(strtolower($document->file_type), ['jpg', 'png']))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Document Preview</h3>
                        <div class="border border-gray-200 rounded-lg overflow-hidden flex justify-center bg-gray-50 p-4">
                            <img
                                src="{{ Storage::disk('public')->url($document->file_path) }}"
                                alt="Document Preview"
                                class="max-w-full max-h-[700px] object-contain rounded"
                            />
                        </div>
                    </div>
                </div>
            @endif

            {{-- Delete Confirmation Modal --}}
            <x-modal name="confirm-document-deletion" :show="false" focusable>
                <form method="POST" action="{{ route('documents.destroy', $document) }}" class="p-6">
                    @csrf
                    @method('DELETE')

                    <h2 class="text-lg font-medium text-gray-900">
                        {{ __('Are you sure you want to delete this document?') }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('This action cannot be undone. The document file will be permanently removed from storage.') }}
                    </p>

                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button x-on:click="$dispatch('close')">
                            {{ __('Cancel') }}
                        </x-secondary-button>

                        <x-danger-button type="submit">
                            {{ __('Delete Document') }}
                        </x-danger-button>
                    </div>
                </form>
            </x-modal>
        </div>
    </div>
</x-app-layout>
