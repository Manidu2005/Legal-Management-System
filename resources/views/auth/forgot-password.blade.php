<x-guest-layout>
    <div class="text-center mb-8">
        <h2 class="font-sans font-semibold text-2xl text-mist-950 dark:text-white tracking-tight">{{ __('Forgot Password') }}</h2>
        <p class="text-mist-500 dark:text-mist-400 mt-1">{{ __('No problem. Just let us know your email address and we will email you a password reset link.') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="label-dynamic">{{ __('Email') }}</label>
            <input id="email" class="input-dynamic" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-xs" />
        </div>

        <button type="submit" class="btn-primary w-full py-3 text-base mt-2">
            {{ __('Email Password Reset Link') }}
        </button>
    </form>
</x-guest-layout>
