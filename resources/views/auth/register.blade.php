<x-guest-layout>
    <div class="flex flex-col items-center justify-center  bg-[#FDFDFC] dark:bg-[#0a0a0a] p-6">

        <!-- Register Form (Single Layer, No Card Effect) -->
        <form method="POST" action="{{ route('register') }}" class="w-full max-w-md bg-transparent p-0">
            @csrf

            <h2 class="text-2xl font-bold text-[#1b1b18] dark:text-white text-center mb-6">Register for {{ config('app.name', 'Laravel') }}</h2>

            <!-- Name -->
            <div class="mb-4">
                <x-input-label for="name" :value="__('Name')" class="text-[#1b1b18] dark:text-white" />
                <x-text-input id="name" class="block mt-1 w-full rounded-md border border-[#1b1b18] dark:border-[#EDEDEC] bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-white focus:ring-[#f53003] focus:border-[#f53003]" 
                              type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2 text-sm text-red-600 dark:text-red-400" />
            </div>

            <!-- Email Address -->
            <div class="mb-4">
                <x-input-label for="email" :value="__('Email')" class="text-[#1b1b18] dark:text-white" />
                <x-text-input id="email" class="block mt-1 w-full rounded-md border border-[#1b1b18] dark:border-[#EDEDEC] bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-white focus:ring-[#f53003] focus:border-[#f53003]" 
                              type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-600 dark:text-red-400" />
            </div>

            <!-- Password -->
            <div class="mb-4">
                <x-input-label for="password" :value="__('Password')" class="text-[#1b1b18] dark:text-white" />
                <x-text-input id="password" class="block mt-1 w-full rounded-md border border-[#1b1b18] dark:border-[#EDEDEC] bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-white focus:ring-[#f53003] focus:border-[#f53003]" 
                              type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-600 dark:text-red-400" />
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-[#1b1b18] dark:text-white" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full rounded-md border border-[#1b1b18] dark:border-[#EDEDEC] bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-white focus:ring-[#f53003] focus:border-[#f53003]" 
                              type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm text-red-600 dark:text-red-400" />
            </div>

            <!-- Submit Button & Login Link -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <a class="underline text-sm text-[#1b1b18] dark:text-[#EDEDEC] hover:text-[#f53003] dark:hover:text-[#f53003]" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <x-primary-button class="bg-[#f53003] hover:bg-[#d62b02] text-white px-6 py-3 rounded-md w-full sm:w-auto">
                    {{ __('Register') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
