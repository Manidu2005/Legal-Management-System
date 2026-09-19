<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('documents.index') }}" class="text-mist-400 dark:text-mist-500 hover:text-mist-950 dark:hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h2 class="heading-section !text-2xl">
                {{ __('Upload Document') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="glass-card overflow-hidden">
            <div class="p-6">
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Case Selection --}}
                    <div class="mb-6">
                        <x-input-label for="case_id" :value="__('Case')" />
                        <select id="case_id" name="case_id" class="input-dynamic mt-1" required>
                            <option value="">{{ __('— Select a case —') }}</option>
                            @foreach ($cases as $case)
                                <option value="{{ $case->id }}" @selected(old('case_id') == $case->id)>
                                    {{ $case->display_name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('case_id')" class="mt-2" />
                    </div>

                    {{-- Document Name --}}
                    <div class="mb-6">
                        <x-input-label for="name" :value="__('Document Name')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                            :value="old('name')" placeholder="{{ __('Optional — defaults to filename when uploading a single file') }}" />
                        <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">{{ __('When uploading multiple files, each document uses its original filename.') }}</p>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    {{-- File Upload --}}
                    <div class="mb-6" id="file-upload-container">
                        <div class="flex items-center justify-between mb-2">
                            <x-input-label :value="__('Document Files')" />
                            <button type="button" id="add-file-btn" class="text-sm text-mist-700 dark:text-mist-300 hover:text-mist-950 dark:hover:text-white font-medium">
                                + {{ __('Add another file') }}
                            </button>
                        </div>

                        <div id="file-inputs" class="space-y-3">
                            <div class="file-input-group flex items-center gap-2">
                                <input type="file" name="documents[]" accept=".pdf,.jpg,.png" multiple
                                    class="block w-full text-sm text-mist-500 dark:text-mist-400
                                        file:me-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-mist-950/5 file:text-mist-800 dark:file:bg-white/10 dark:file:text-mist-200
                                        hover:file:bg-mist-950/10 dark:hover:file:bg-white/10
                                        cursor-pointer border border-mist-950/20 dark:border-white/20 rounded-md"
                                    required />
                                <button type="button" class="remove-file-btn hidden text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 p-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-mist-500 dark:text-mist-400">{{ __('Accepted formats: PDF, JPG, PNG.') }} <span class="font-medium">{{ __('25MB max per file') }}</span>. {{ __('You can select multiple files at once or add more inputs.') }}</p>
                        <x-input-error :messages="$errors->get('documents')" class="mt-2" />
                        <x-input-error :messages="$errors->get('documents.*')" class="mt-2" />
                    </div>

                    {{-- Category --}}
                    <div class="mb-6">
                        <x-input-label for="category" :value="__('Category')" />
                        <select id="category" name="category" class="input-dynamic mt-1" required>
                            <option value="">{{ __('— Select a category —') }}</option>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('file-inputs');
            const addBtn = document.getElementById('add-file-btn');

            addBtn.addEventListener('click', function() {
                const groups = container.querySelectorAll('.file-input-group');
                const clone = groups[0].cloneNode(true);

                const input = clone.querySelector('input[type="file"]');
                input.value = '';
                input.removeAttribute('required');

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
