<x-guest-layout>
    <div class="text-center mb-8">
        <h2 class="font-sans font-semibold text-2xl text-mist-950 dark:text-white tracking-tight">{{ __('Confirm Password') }}</h2>
        <p class="text-mist-500 dark:text-mist-400 mt-1">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <!-- Password -->
        <div>
            <label for="password" class="label-dynamic">{{ __('Password') }}</label>
            <input id="password" class="input-dynamic" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs" />
        </div>

        <button type="submit" class="btn-primary w-full py-3 text-base mt-2">
            {{ __('Confirm') }}
        </button>
    </form>
</x-guest-layout>
