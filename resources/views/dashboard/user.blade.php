<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Pieturas</h2>
    </x-slot>

    <div class="p-6 space-y-4" x-data="busStopSearch()" x-init="init()">
        <p>Welcome, {{ auth()->user()->name }}!</p>
        <p>You are logged in as a user.</p>

        {{-- Admin-only button --}}
        @if(auth()->user()->is_admin)
            <a href="{{ route('admin.dashboard') }}"
               class="inline-block px-4 py-2 text-white rounded transition hover:brightness-90"
               style="background-color: #ff3e00;">
                Admin Dashboard
            </a>
        @endif

        {{-- Search Input --}}
        <input type="text"
               placeholder="Search bus stops..."
               x-model="query"
               @input="fetchStops(1)"
               class="border p-2 rounded w-full mb-4">

        {{-- Bus Stops Table --}}
        <table class="w-full border-collapse border mt-4">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border p-2 text-left">Name</th>
                    <th class="border p-2 text-left">Code</th>
                    <th class="border p-2 text-left">Street</th>
                    <th class="border p-2 text-left">Municipality</th>
                    <th class="border p-2 text-left">Road Side</th> <!-- NEW COLUMN -->
                </tr>
            </thead>
            <tbody>
                <template x-for="stop in stops.data" :key="stop.id">
                    <tr>
                        <td class="border p-2" x-text="stop.name"></td>
                        <td class="border p-2" x-text="stop.stop_code"></td>
                        <td class="border p-2" x-text="stop.street"></td>
                        <td class="border p-2" x-text="stop.municipality"></td>
                        <td class="border p-2" x-text="stop.road_side"></td> <!-- NEW COLUMN -->
                    </tr>
                </template>
                <tr x-show="stops.data.length === 0">
                    <td colspan="5" class="border p-2 text-center">No results found.</td>
                </tr>
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="mt-4 flex space-x-2 flex-wrap">
            <button 
                class="px-3 py-1 border rounded"
                :disabled="stops.current_page === 1"
                @click="goToPage(1)">
                « First
            </button>

            <button 
                class="px-3 py-1 border rounded"
                :disabled="stops.current_page === 1"
                @click="goToPage(stops.current_page - 1)">
                ‹ Prev
            </button>

            <template x-for="page in paginationWindow()" :key="page">
                <button 
                    class="px-3 py-1 border rounded"
                    :class="{'bg-gray-300': page === stops.current_page}"
                    @click="goToPage(page)">
                    <span x-text="page"></span>
                </button>
            </template>

            <button 
                class="px-3 py-1 border rounded"
                :disabled="stops.current_page === stops.last_page"
                @click="goToPage(stops.current_page + 1)">
                Next ›
            </button>

            <button 
                class="px-3 py-1 border rounded"
                :disabled="stops.current_page === stops.last_page"
                @click="goToPage(stops.last_page)">
                Last »
            </button>
        </div>
    </div>

    <script>
        function busStopSearch() {
            return {
                query: '',
                stops: { data: [], current_page: 1, last_page: 1 },

                init() {
                    this.fetchStops();
                },

                fetchStops(page = 1) {
                    fetch(`/dashboard/search-stops?query=${encodeURIComponent(this.query)}&page=${page}`)
                        .then(res => res.json())
                        .then(data => this.stops = data);
                },

                goToPage(page) {
                    if (page < 1 || page > this.stops.last_page) return;
                    this.fetchStops(page);
                },

                paginationWindow() {
                    let total = this.stops.last_page;
                    let current = this.stops.current_page;
                    let delta = 2;
                    let range = [];

                    for (let i = current - delta; i <= current + delta; i++) {
                        if (i > 0 && i <= total) range.push(i);
                    }

                    return range;
                }
            }
        }
    </script>
</x-app-layout>
