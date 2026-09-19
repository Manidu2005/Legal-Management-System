<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="heading-display !text-3xl">
                {{ __('User Management') }}
            </h2>
            <a href="{{ route('users.create') }}" class="btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('Create User') }}
            </a>
        </div>
    </x-slot>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert-success mb-6">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter --}}
    <div class="glass-card overflow-hidden mb-6">
        <div class="p-4">
            <form method="GET" action="{{ route('users.index') }}" class="flex items-center gap-4">
                <x-input-label for="role" value="{{ __('Filter by Role') }}" class="whitespace-nowrap" />
                <select id="role" name="role" class="input-dynamic !py-2 !text-sm !w-auto">
                    <option value="">{{ __('All Roles') }}</option>
                    <option value="partner" {{ request('role') === 'partner' ? 'selected' : '' }}>{{ __('Partner') }}</option>
                    <option value="associate" {{ request('role') === 'associate' ? 'selected' : '' }}>{{ __('Associate') }}</option>
                    <option value="clerk" {{ request('role') === 'clerk' ? 'selected' : '' }}>{{ __('Clerk') }}</option>
                </select>
                <x-primary-button type="submit">
                    {{ __('Filter') }}
                </x-primary-button>
                @if(request('role'))
                    <a href="{{ route('users.index') }}" class="link-subtle underline">{{ __('Clear') }}</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-dynamic">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Branch') }}</th>
                        <th>{{ __('Rate') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-950/10 dark:divide-white/10">
                    @forelse ($users as $user)
                        <tr class="table-row-dynamic">
                            <td>
                                <div class="text-sm font-medium text-mist-950 dark:text-white">{{ $user->name }}</div>
                            </td>
                            <td>
                                <div class="text-sm text-mist-500 dark:text-mist-400">{{ $user->email }}</div>
                            </td>
                            <td>
                                @if($user->role === 'partner')
                                    <span class="badge-dynamic bg-mist-950/10 dark:bg-white/10 text-mist-800 dark:text-mist-200 border-0">{{ __('Partner') }}</span>
                                @elseif($user->role === 'associate')
                                    <span class="badge-dynamic bg-sky-100 dark:bg-sky-500/10 text-sky-800 dark:text-sky-400 border-0">{{ __('Associate') }}</span>
                                @else
                                    <span class="badge-dynamic bg-mist-950/5 dark:bg-white/5 text-mist-700 dark:text-mist-300 border-0">{{ __('Clerk') }}</span>
                                @endif
                            </td>
                            <td class="text-sm text-mist-500 dark:text-mist-400">{{ $user->branch }}</td>
                            <td class="text-sm text-mist-500 dark:text-mist-400">
                                @if($user->flat_appearance_rate)
                                    LKR {{ number_format($user->flat_appearance_rate, 2) }}
                                @else
                                    <span class="text-mist-400 dark:text-mist-500">—</span>
                                @endif
                            </td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="badge-dynamic bg-emerald-100 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-400 border-0">{{ __('Active') }}</span>
                                @else
                                    <span class="badge-dynamic bg-red-100 dark:bg-red-500/10 text-red-800 dark:text-red-400 border-0">{{ __('Suspended') }}</span>
                                @endif
                            </td>
                            <td class="text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('users.edit', $user) }}" class="link-subtle">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('users.toggle-status', $user) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        @if($user->status === 'active')
                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300" onclick="return confirm('{{ __('Are you sure you want to suspend this user?') }}')">
                                                {{ __('Suspend') }}
                                            </button>
                                        @else
                                            <button type="submit" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300">
                                                {{ __('Activate') }}
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-mist-500 dark:text-mist-400">
                                <svg class="mx-auto h-12 w-12 text-mist-400 dark:text-mist-500 mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                <p>{{ __('No users found.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($users->hasPages())
            <div class="px-6 py-4 border-t border-mist-950/10 dark:border-white/10">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
