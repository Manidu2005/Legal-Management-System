<x-guest-layout>
    <div class="text-center mb-8">
        <h2 class="font-heading font-bold text-2xl text-slate-900 tracking-tight">Create an account</h2>
        <p class="text-slate-500 mt-1">Join LexLanka to manage your practice</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="label-dynamic">Full Name</label>
            <input id="name" class="input-dynamic" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="John Doe" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="label-dynamic">Email Address</label>
            <input id="email" class="input-dynamic" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="name@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="label-dynamic">Password</label>
            <input id="password" class="input-dynamic" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-xs" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="label-dynamic">Confirm Password</label>
            <input id="password_confirmation" class="input-dynamic" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-500 text-xs" />
        </div>

        <button type="submit" class="btn-primary w-full py-3 text-base mt-2">
            Create Account
        </button>

        <p class="text-center text-sm text-slate-500 mt-6">
            Already registered? 
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500 transition-colors">Sign in here</a>
        </p>
    </form>
</x-guest-layout>
