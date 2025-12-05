<x-guest-layout>
    <div class="flex flex-col items-center justify-center  bg-[#FDFDFC] dark:bg-[#0a0a0a] p-6">
        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <!-- Login Form (Single Layer, No Card Effect) -->
        <form method="POST" action="{{ route('login') }}" class="w-full max-w-md bg-transparent p-0">
            @csrf

            <h2 class="text-2xl font-bold text-[#1b1b18] dark:text-white text-center mb-6">Log in to {{ config('app.name', 'Laravel') }}</h2>

            <!-- Email Address -->
            <div class="mb-4">
                <x-input-label for="email" :value="__('Email')" class="text-[#1b1b18] dark:text-white" />
                <x-text-input id="email" class="block mt-1 w-full rounded-md border border-[#1b1b18] dark:border-[#EDEDEC] bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-white focus:ring-[#f53003] focus:border-[#f53003]" 
                              type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-600 dark:text-red-400" />
            </div>

            <!-- Password -->
            <div class="mb-4">
                <x-input-label for="password" :value="__('Password')" class="text-[#1b1b18] dark:text-white" />
                <x-text-input id="password" class="block mt-1 w-full rounded-md border border-[#1b1b18] dark:border-[#EDEDEC] bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-white focus:ring-[#f53003] focus:border-[#f53003]" 
                              type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-600 dark:text-red-400" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center mb-6">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 dark:border-gray-700 text-[#f53003] shadow-sm focus:ring-[#f53003] dark:focus:ring-[#f53003] dark:bg-[#0a0a0a]" name="remember">
                    <span class="ms-2 text-sm text-[#1b1b18] dark:text-[#EDEDEC]">{{ __('Remember me') }}</span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-[#1b1b18] dark:text-[#EDEDEC] hover:text-[#f53003] dark:hover:text-[#f53003]" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-primary-button class="bg-[#f53003] hover:bg-[#d62b02] text-white px-6 py-3 rounded-md w-full sm:w-auto">
                    {{ __('Log in') }}
                </x-primary-button>
            </div>

            <!-- Optional Register Link -->
            @if (Route::has('register'))
                <p class="mt-6 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Don't have an account? 
                    <a href="{{ route('register') }}" class="text-[#f53003] hover:text-[#d62b02] font-medium">Register</a>
                </p>
            @endif
        </form>
    </div>
</x-guest-layout>
