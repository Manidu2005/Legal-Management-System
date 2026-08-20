<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('cases.show', $case) }}" class="text-gray-400 transition hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Edit Case') }} — {{ $case->display_name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <form method="POST" action="{{ route('cases.update', $case) }}" class="p-6 sm:p-8">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        {{-- Client --}}
                        <div>
                            <x-input-label for="client_id" :value="__('Client')" />
                            <select id="client_id" name="client_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                <option value="">{{ __('Select a client...') }}</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" @selected(old('client_id', $case->client_id) == $client->id)>
                                        {{ $client->name }} ({{ $client->nic }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('client_id')" class="mt-2" />
                        </div>

                        {{-- Case Name --}}
                        <div>
                            <x-input-label for="name" :value="__('Case Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                :value="old('name', $case->name)" placeholder="{{ __('Optional — e.g. Smith v. Jones') }}" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        {{-- Assigned Attorney --}}
                        <div>
                            <x-input-label for="assigned_attorney_id" :value="__('Assigned Attorney')" />
                            <select id="assigned_attorney_id" name="assigned_attorney_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                <option value="">{{ __('Select an attorney...') }}</option>
                                @foreach($attorneys as $attorney)
                                    <option value="{{ $attorney->id }}" @selected(old('assigned_attorney_id', $case->assigned_attorney_id) == $attorney->id)>
                                        {{ $attorney->name }} ({{ ucfirst($attorney->role) }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('assigned_attorney_id')" class="mt-2" />
                        </div>

                        {{-- Case Type --}}
                        <div>
                            <x-input-label for="case_type" :value="__('Case Type')" />
                            <select id="case_type" name="case_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="" disabled>{{ __('Select case type...') }}</option>
                                <optgroup label="{{ __('Civil Law') }}">
                                    <option value="Civil - Breach of Contract" @selected(old('case_type', $case->case_type) == 'Civil - Breach of Contract')>{{ __('Breach of Contract') }}</option>
                                    <option value="Civil - Defamation" @selected(old('case_type', $case->case_type) == 'Civil - Defamation')>{{ __('Defamation') }}</option>
                                    <option value="Civil - Money Recovery" @selected(old('case_type', $case->case_type) == 'Civil - Money Recovery')>{{ __('Money Recovery') }}</option>
                                </optgroup>
                                <optgroup label="{{ __('Criminal Law') }}">
                                    <option value="Criminal - Fraud & Forgery" @selected(old('case_type', $case->case_type) == 'Criminal - Fraud & Forgery')>{{ __('Fraud & Forgery') }}</option>
                                    <option value="Criminal - Assault" @selected(old('case_type', $case->case_type) == 'Criminal - Assault')>{{ __('Assault') }}</option>
                                    <option value="Criminal - Narcotics" @selected(old('case_type', $case->case_type) == 'Criminal - Narcotics')>{{ __('Narcotics') }}</option>
                                </optgroup>
                                <optgroup label="{{ __('Property & Land') }}">
                                    <option value="Property - Partition Cases" @selected(old('case_type', $case->case_type) == 'Property - Partition Cases')>{{ __('Partition Cases') }}</option>
                                    <option value="Property - Land Eviction" @selected(old('case_type', $case->case_type) == 'Property - Land Eviction')>{{ __('Land Eviction') }}</option>
                                </optgroup>
                                <optgroup label="{{ __('Family Law') }}">
                                    <option value="Family - Divorce" @selected(old('case_type', $case->case_type) == 'Family - Divorce')>{{ __('Divorce') }}</option>
                                    <option value="Family - Child Custody" @selected(old('case_type', $case->case_type) == 'Family - Child Custody')>{{ __('Child Custody') }}</option>
                                </optgroup>
                                <optgroup label="{{ __('Other') }}">
                                    <option value="Other" @selected(old('case_type', $case->case_type) == 'Other')>{{ __('Other') }}</option>
                                </optgroup>
                            </select>
                            <x-input-error :messages="$errors->get('case_type')" class="mt-2" />
                        </div>

                        {{-- Status --}}
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                <option value="pending" @selected(old('status', $case->status) === 'pending')>{{ __('Pending') }}</option>
                                <option value="active" @selected(old('status', $case->status) === 'active')>{{ __('Active') }}</option>
                                <option value="trial_scheduled" @selected(old('status', $case->status) === 'trial_scheduled')>{{ __('Trial Scheduled') }}</option>
                                <option value="judgment_delivered" @selected(old('status', $case->status) === 'judgment_delivered')>{{ __('Judgment Delivered') }}</option>
                                <option value="case_closed" @selected(old('status', $case->status) === 'case_closed')>{{ __('Case Closed') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-8 flex items-center justify-end gap-4 border-t border-gray-200 pt-6">
                        <a href="{{ route('cases.show', $case) }}"
                           class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button>
                            {{ __('Update Case') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>

            @can('manageAccessCode', $case)
                <div class="mt-6 overflow-hidden rounded-lg bg-white shadow-sm">
                    <form method="POST" action="{{ route('cases.access-code.update', $case) }}" class="p-6 sm:p-8">
                        @csrf
                        @method('PATCH')

                        <h3 class="text-sm font-semibold uppercase tracking-widest text-gray-700">
                            {{ __('Case Access Code') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('A clerk must enter this code before they can view this case or its documents.') }}
                            @if($case->hasAccessCode())
                                {{ __('An access code is currently set.') }}
                            @else
                                {{ __('No access code is set — clerks cannot view this case yet.') }}
                            @endif
                        </p>

                        <div class="mt-4 max-w-sm">
                            <x-input-label for="access_code" :value="__('New Access Code')" />
                            <x-text-input id="access_code" name="access_code" type="password"
                                class="mt-1 block w-full" autocomplete="off"
                                placeholder="{{ $case->hasAccessCode() ? __('Enter a new code to change it') : __('Set an access code') }}" />
                            <x-input-error :messages="$errors->get('access_code')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-secondary-button type="submit">
                                {{ $case->hasAccessCode() ? __('Change Code') : __('Set Code') }}
                            </x-secondary-button>
                        </div>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
