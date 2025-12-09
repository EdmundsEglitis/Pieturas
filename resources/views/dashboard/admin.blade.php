<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-red-600">Admin Dashboard</h2>
    </x-slot>

    <div class="p-6 space-y-4">
        <p>Welcome Admin, {{ auth()->user()->name }}!</p>

        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('admin.busstop.form') }}" class="block p-4 bg-gray-200 rounded hover:bg-gray-300">
                Import Bus Stops
            </a>

            <a href="{{ route('road-segments.index') }}" class="block p-4 bg-blue-200 rounded hover:bg-blue-300">
                Manage Road Segments
            </a>

            @if($lastUpdated)
                <p>Last updated bus stop: {{ $lastUpdated->diffForHumans() }}</p>
            @else
                <p>No bus stops updated yet.</p>
            @endif
        </div>
    </div>
</x-app-layout>
