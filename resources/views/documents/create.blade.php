<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <a href="{{ route('documents.index') }}" class="text-gray-500 hover:text-gray-700 me-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Upload Document') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Case Selection --}}
                        <div class="mb-6">
                            <x-input-label for="case_id" :value="__('Case')" />
                            <select id="case_id" name="case_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required>
                                <option value="">— Select a case —</option>
                                @foreach ($cases as $case)
                                    <option value="{{ $case->id }}" @selected(old('case_id') == $case->id)>
                                        Case #{{ $case->id }} — {{ $case->client->name ?? 'N/A' }} ({{ ucfirst($case->case_type ?? 'N/A') }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('case_id')" class="mt-2" />
                        </div>

                        {{-- File Upload --}}
                        <div class="mb-6" id="file-upload-container">
                            <div class="flex items-center justify-between mb-2">
                                <x-input-label :value="__('Document Files')" />
                                <button type="button" id="add-file-btn" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">
                                    + Add another file
                                </button>
                            </div>
                            
                            <div id="file-inputs" class="space-y-3">
                                <div class="file-input-group flex items-center gap-2">
                                    <input type="file" name="documents[]" accept=".pdf,.jpg,.png" multiple
                                        class="block w-full text-sm text-gray-500
                                            file:me-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-indigo-50 file:text-indigo-700
                                            hover:file:bg-indigo-100
                                            cursor-pointer border border-gray-300 rounded-md"
                                        required />
                                    <button type="button" class="remove-file-btn hidden text-red-500 hover:text-red-700 p-2">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Accepted formats: PDF, JPG, PNG. <span class="font-medium">25MB max per file</span>. You can select multiple files at once or add more inputs.</p>
                            <x-input-error :messages="$errors->get('documents')" class="mt-2" />
                            <x-input-error :messages="$errors->get('documents.*')" class="mt-2" />
                        </div>

                        {{-- Category --}}
                        <div class="mb-6">
                            <x-input-label for="category" :value="__('Category')" />
                            <select id="category" name="category"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required>
                                <option value="">— Select a category —</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>
                                        {{ ucfirst($category) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category')" class="mt-2" />
                        </div>

                        {{-- Submit --}}
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('documents.index') }}">
                                <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                            </a>
                            <x-primary-button>
                                <svg class="w-4 h-4 me-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                </svg>
                                {{ __('Upload Document') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('file-inputs');
            const addBtn = document.getElementById('add-file-btn');
            
            addBtn.addEventListener('click', function() {
                const groups = container.querySelectorAll('.file-input-group');
                const clone = groups[0].cloneNode(true);
                
                // Clear the input value
                const input = clone.querySelector('input[type="file"]');
                input.value = '';
                input.removeAttribute('required'); // Only the first is required
                
                // Show the remove button
                const removeBtn = clone.querySelector('.remove-file-btn');
                removeBtn.classList.remove('hidden');
                removeBtn.addEventListener('click', function() {
                    clone.remove();
                });
                
                container.appendChild(clone);
            });
        });
    </script>
</x-app-layout>
