<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Pieturas</h2>
    </x-slot>

    <div class="p-6 space-y-4" x-data="busStopSearch()" x-init="init()">



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

        {{-- Button to show selected stops --}}
        <button
            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition mb-2"
            @click="
                if(selectedStops.length === 0) { alert('Please select at least one bus stop'); return; }
                const url = `/dashboard/map?stops=${selectedStops.join(',')}`;
                window.location.href = url;
            "
            style="background-color: #ff3e00;">
            
            Show Selected on Map
        </button>

        {{-- Toggle expanded table --}}
        <button class="px-4 py-2 bg-gray-300 rounded mb-2" @click="expanded = !expanded">
            <span x-text="expanded ? 'Collapse Table' : 'Expand Table'"></span>
        </button>

        {{-- Table wrapper --}}
        <div class="overflow-x-auto border rounded">
            <table class="w-full border-collapse border table-auto">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border p-2">Select</th>
                        <template x-for="col in (expanded ? fullColumns : collapsedColumns)" :key="col.key">
                            <th class="border p-2 cursor-pointer whitespace-nowrap"
                                @click="sortByColumn(col.key)">
                                <span x-text="col.label"></span>
                                <span x-show="sort_column === col.key">
                                    <span x-text="sort_order === 'asc' ? '▲' : '▼'"></span>
                                </span>
                            </th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="stop in visibleStops" :key="stop.id">
                        <tr>
                            {{-- Checkbox --}}
                            <td class="border p-2 text-center">
                                <input type="checkbox" :value="stop.id" x-model="selectedStops">
                            </td>

                            <template x-for="col in (expanded ? fullColumns : collapsedColumns)" :key="col.key">
                                <td class="border p-2" x-text="stop[col.key]"></td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="stops.data.length === 0">
                        <td :colspan="expanded ? fullColumns.length+1 : collapsedColumns.length+1" class="border p-2 text-center">
                            No results found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4 flex space-x-2 flex-wrap" x-show="!expanded">
            <button class="px-3 py-1 border rounded" :disabled="stops.current_page === 1" @click="goToPage(1)">« First</button>
            <button class="px-3 py-1 border rounded" :disabled="stops.current_page === 1" @click="goToPage(stops.current_page - 1)">‹ Prev</button>
            <template x-for="page in paginationWindow()" :key="page">
                <button class="px-3 py-1 border rounded" :class="{'bg-gray-300': page === stops.current_page}" @click="goToPage(page)">
                    <span x-text="page"></span>
                </button>
            </template>
            <button class="px-3 py-1 border rounded" :disabled="stops.current_page === stops.last_page" @click="goToPage(stops.current_page + 1)">Next ›</button>
            <button class="px-3 py-1 border rounded" :disabled="stops.current_page === stops.last_page" @click="goToPage(stops.last_page)">Last »</button>
        </div>

    </div>

    <script>
        function busStopSearch() {
            return {
                query: '',
                stops: { data: [], current_page: 1, last_page: 1 },
                sort_column: 'name',
                sort_order: 'asc',
                expanded: false,
                selectedStops: [],

                collapsedColumns: [
                    { label: 'Name', key: 'name' },
                    { label: 'Stop Code', key: 'stop_code' },
                    { label: 'Street', key: 'street' },
                    { label: 'Municipality', key: 'municipality' },
                    { label: 'Road Side', key: 'road_side' },
                ],

                fullColumns: [
                    { label: 'ID', key: 'id' },
                    { label: 'Name', key: 'name' },
                    { label: 'Stop Code', key: 'stop_code' },
                    { label: 'Unified Code', key: 'unified_code' },
                    { label: 'Latitude', key: 'latitude' },
                    { label: 'Longitude', key: 'longitude' },
                    { label: 'Road Side', key: 'road_side' },
                    { label: 'Road Number', key: 'road_number' },
                    { label: 'Street', key: 'street' },
                    { label: 'Municipality', key: 'municipality' },
                    { label: 'Parish', key: 'parish' },
                    { label: 'Village', key: 'village' },
                ],

                init() {
                    this.fetchStops();
                },

                fetchStops(page = 1) {
                    fetch(`/dashboard/search-stops?query=${encodeURIComponent(this.query)}&page=${page}&sort_by=${this.sort_column}&sort_order=${this.sort_order}`)
                        .then(res => res.json())
                        .then(data => this.stops = data);
                },

                get visibleStops() {
                    return this.stops.data;
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
                },

                sortByColumn(column) {
                    if (this.sort_column === column) {
                        this.sort_order = this.sort_order === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sort_column = column;
                        this.sort_order = 'asc';
                    }
                    this.fetchStops(1);
                }
            }
        }
    </script>
</x-app-layout>
