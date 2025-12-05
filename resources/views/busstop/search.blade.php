<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Search Bus Stops</h2>
    </x-slot>

    <div class="p-6 space-y-4">
        <form action="{{ route('busstop.search.results') }}" method="GET" class="flex gap-2">
            <input type="text" name="query" placeholder="Enter bus stop name or code"
                   class="border p-2 rounded w-full" required>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                Search
            </button>
        </form>
    </div>
</x-app-layout>
