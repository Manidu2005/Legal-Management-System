<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="text-center mb-8">
        <h2 class="font-sans font-semibold text-2xl text-mist-950 dark:text-white tracking-tight">Welcome back</h2>
        <p class="text-mist-500 dark:text-mist-400 mt-1">Sign in to your LexLanka workspace</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="label-dynamic">Email Address</label>
            <input id="email" class="input-dynamic" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex justify-between items-center mb-1.5">
                <label for="password" class="label-dynamic !mb-0">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-sm text-mist-700 dark:text-mist-300 hover:text-mist-950 dark:hover:text-white font-medium transition-colors" href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input id="password" class="input-dynamic" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Remember Me -->
        <div class="block">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-mist-950/10 dark:border-white/10 text-mist-950 shadow-sm focus:ring-mist-500/20" name="remember">
                <span class="ms-2 text-sm text-mist-700 dark:text-mist-300">Remember me for 30 days</span>
            </label>
        </div>

        <button type="submit" class="btn-primary w-full py-3 text-base mt-2">
            Sign in
        </button>


    </form>
</x-guest-layout>
