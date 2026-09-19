<x-guest-layout>
    <div class="text-center mb-8">
        <h2 class="font-sans font-semibold text-2xl text-mist-950 dark:text-white tracking-tight">{{ __('Reset Password') }}</h2>
        <p class="text-mist-500 dark:text-mist-400 mt-1">{{ __('Choose a new password for your account') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <label for="email" class="label-dynamic">{{ __('Email') }}</label>
            <input id="email" class="input-dynamic" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="label-dynamic">{{ __('Password') }}</label>
            <input id="password" class="input-dynamic" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="label-dynamic">{{ __('Confirm Password') }}</label>
            <input id="password_confirmation" class="input-dynamic" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500 text-xs" />
        </div>

        <button type="submit" class="btn-primary w-full py-3 text-base mt-2">
            {{ __('Reset Password') }}
        </button>
    </form>
</x-guest-layout>
