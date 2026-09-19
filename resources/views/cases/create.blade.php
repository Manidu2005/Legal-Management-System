<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <a href="{{ route('cases.index') }}" class="text-mist-400 dark:text-mist-500 hover:text-mist-950 dark:hover:text-white transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h2 class="heading-section !text-2xl">
                {{ __('Create New Case') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="glass-card overflow-hidden">
            <form method="POST" action="{{ route('cases.store') }}" class="p-6 sm:p-8"
                  x-data="{
                      tree: @js($caseCategoryTree),
                      mainId: '{{ old('main_category_id') }}',
                      groupId: '{{ old('group_category_id') }}',
                      leafId: '{{ old('case_category_id') }}',
                      get groups() {
                          const main = this.tree.find(m => String(m.id) === String(this.mainId));
                          return main ? main.children : [];
                      },
                      get leaves() {
                          const group = this.groups.find(g => String(g.id) === String(this.groupId));
                          return group ? group.children : [];
                      },
                      get selectedLeafName() {
                          const leaf = this.leaves.find(l => String(l.id) === String(this.leafId));
                          return leaf ? leaf.name : null;
                      }
                  }">
                @csrf

                <div class="space-y-6">
                    {{-- Client --}}
                    <div>
                        <x-input-label for="client_id" :value="__('Client')" />
                        <select id="client_id" name="client_id" class="input-dynamic mt-1" required>
                            <option value="">{{ __('Select a client...') }}</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @selected(old('client_id', request('client_id')) == $client->id)>
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
                            :value="old('name')" placeholder="{{ __('Optional — e.g. Smith v. Jones') }}" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    {{-- Assigned Attorney --}}
                    <div>
                        <x-input-label for="assigned_attorney_id" :value="__('Assigned Attorney')" />
                        <select id="assigned_attorney_id" name="assigned_attorney_id" class="input-dynamic mt-1" required>
                            <option value="">{{ __('Select an attorney...') }}</option>
                            @foreach($attorneys as $attorney)
                                <option value="{{ $attorney->id }}" @selected(old('assigned_attorney_id') == $attorney->id)>
                                    {{ $attorney->name }} ({{ ucfirst($attorney->role) }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('assigned_attorney_id')" class="mt-2" />
                    </div>

                    {{-- Case Type — cascading Main Type / Group / Specific Type --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="main_category_id" :value="__('Main Type')" />
                            <select id="main_category_id" name="main_category_id" class="input-dynamic mt-1"
                                    x-model="mainId" @change="groupId = ''; leafId = ''" required>
                                <option value="">{{ __('Select...') }}</option>
                                <template x-for="main in tree" :key="main.id">
                                    <option :value="main.id" x-text="main.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="group_category_id" :value="__('Group')" />
                            <select id="group_category_id" name="group_category_id" class="input-dynamic mt-1"
                                    x-model="groupId" @change="leafId = ''" :disabled="!mainId" required>
                                <option value="">{{ __('Select...') }}</option>
                                <template x-for="group in groups" :key="group.id">
                                    <option :value="group.id" x-text="group.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="case_category_id" :value="__('Specific Type')" />
                            <select id="case_category_id" name="case_category_id" class="input-dynamic mt-1"
                                    x-model="leafId" :disabled="!groupId" required>
                                <option value="">{{ __('Select...') }}</option>
                                <template x-for="leaf in leaves" :key="leaf.id">
                                    <option :value="leaf.id" x-text="leaf.name"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('case_category_id')" class="mt-2" />
                        </div>
                    </div>

                    {{-- "Other" detail --}}
                    <div x-show="selectedLeafName === 'Other'" x-cloak>
                        <x-input-label for="case_type_other" :value="__('Please specify')" />
                        <x-text-input id="case_type_other" name="case_type_other" type="text" class="mt-1 block w-full"
                            :value="old('case_type_other')" placeholder="{{ __('Briefly describe the case type') }}" />
                        <x-input-error :messages="$errors->get('case_type_other')" class="mt-2" />
                    </div>

                    {{-- Court / Forum --}}
                    <div>
                        <x-input-label for="court_id" :value="__('Court / Forum')" />
                        <select id="court_id" name="court_id" class="input-dynamic mt-1">
                            <option value="">{{ __('Not yet determined') }}</option>
                            @foreach($courts->groupBy('tier') as $tier => $tierCourts)
                                <optgroup label="{{ \App\Models\Court::TIERS[$tier] ?? $tier }}">
                                    @foreach($tierCourts as $tierCourt)
                                        <option value="{{ $tierCourt->id }}" @selected(old('court_id') == $tierCourt->id)>{{ $tierCourt->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('court_id')" class="mt-2" />
                    </div>

                    {{-- Applicable Law --}}
                    <div>
                        <x-input-label for="applicable_law" :value="__('Applicable Law')" />
                        <select id="applicable_law" name="applicable_law" class="input-dynamic mt-1">
                            <option value="">{{ __('General law (default)') }}</option>
                            @foreach(\App\Models\LegalCase::APPLICABLE_LAWS as $lawValue => $lawLabel)
                                <option value="{{ $lawValue }}" @selected(old('applicable_law') == $lawValue)>{{ __($lawLabel) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-mist-400 dark:text-mist-500">{{ __('Only relevant for Family, Property or Succession matters governed by a personal-law system.') }}</p>
                        <x-input-error :messages="$errors->get('applicable_law')" class="mt-2" />
                    </div>

                    {{-- Status --}}
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="input-dynamic mt-1" required>
                            <option value="pending" @selected(old('status', 'pending') === 'pending')>{{ __('Pending') }}</option>
                            <option value="active" @selected(old('status') === 'active')>{{ __('Active') }}</option>
                            <option value="trial_scheduled" @selected(old('status') === 'trial_scheduled')>{{ __('Trial Scheduled') }}</option>
                            <option value="judgment_delivered" @selected(old('status') === 'judgment_delivered')>{{ __('Judgment Delivered') }}</option>
                            <option value="case_closed" @selected(old('status') === 'case_closed')>{{ __('Case Closed') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>

                {{-- Actions --}}
                <div class="mt-8 flex items-center justify-end gap-4 border-t border-mist-950/10 dark:border-white/10 pt-6">
                    <a href="{{ route('cases.index') }}">
                        <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                    </a>
                    <x-primary-button>
                        {{ __('Create Case') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
