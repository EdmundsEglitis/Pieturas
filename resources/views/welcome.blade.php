<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] flex items-center justify-center min-h-screen p-6">

    <!-- Wrapper for centering -->
    <div class="w-full max-w-md flex flex-col items-center text-center">

        <!-- Header with Logo -->
        <header class="flex flex-col items-center gap-4 mb-10">
            <div class="flex items-center gap-2">
                <img src="{{ asset('cropped-cropped-cropped-uso_logo_icon-e1716812169597.png') }}" alt="Logo" class="h-12 w-auto">
                <span class="text-2xl font-semibold text-[#1b1b18] dark:text-white"></span>
                
                
            </div>
        <!-- Main Content -->
        <main class="flex flex-col items-center gap-4">
            <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-white mb-2">Welcome to {{ config('app.name', 'Laravel') }}</h1>
            <p class="text-[#706f6c] dark:text-[#A1A09A] mb-6">Start building amazing applications quickly and efficiently with Laravel.</p>
        </main>
            <!-- Auth Buttons -->
            @if (Route::has('login'))
                <nav class="flex gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="px-6 py-3 bg-[#f53003] text-white rounded font-medium hover:bg-[#d62b02] transition">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-6 py-3 bg-[#f53003] text-white rounded font-medium hover:bg-[#d62b02] transition">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="px-6 py-3 border border-[#1b1b18] dark:border-[#EDEDEC] text-[#1b1b18] dark:text-[#EDEDEC] rounded font-medium hover:bg-[#1b1b18] hover:text-white transition">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>



    </div>

</body>
</html>
