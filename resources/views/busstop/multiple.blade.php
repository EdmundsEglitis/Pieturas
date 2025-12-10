<x-app-layout>
<x-slot name="header">
<h2 class="text-xl font-semibold">Selected Bus Stops</h2>
</x-slot>
<style>
.car-icon {
    pointer-events: none;
}

.car-body {
    width: 20px;
    height: 10px;
    background: red;
    border-radius: 2px;
    transform-origin: center center;
}
#radar-log-container {
    position: absolute;
    left: 12px;
    top: 120px;
    width: 360px;
    max-height: 320px;
    background: #fff;
    border: 1px solid #bbb;
    font-size: 12px;
    display: flex;
    flex-direction: column;
    z-index: 1000;
}

.radar-log-header,
.radar-log-footer {
    padding: 6px 8px;
    background: #f0f0f0;
    font-weight: bold;
    border-bottom: 1px solid #ccc;
}

.radar-log-footer {
    border-top: 1px solid #ccc;
    border-bottom: none;
}

#radar-log {
    padding: 6px;
    overflow-y: auto;
    flex: 1;
    font-family: monospace;
}

.radar-log-entry {
    margin-bottom: 6px;
    padding-bottom: 4px;
    border-bottom: 1px dashed #ddd;
}

.radar-log-entry.speeding {
    color: #b00020;
}

.radar-log-entry.legal {
    color: #2e7d32;
}


</style>
<div class="p-6 space-y-6">
    <p class="text-gray-700 dark:text-gray-300">
        You have selected <strong>{{ count($stops) }}</strong> bus stop(s).
    </p>

    <div class="relative">
        <div id="map" class="w-full h-[600px] rounded border shadow"></div>

        <!-- Speed Legend -->
        <div class="p-2 rounded bg-white shadow absolute top-4 right-4 z-[9999] text-sm">
            <h4 class="font-bold mb-1">Speed Limit (Latvia)</h4>

            @php
                $colors = [
                    20  => '#e6194B', 30  => '#f58231', 40  => '#ffe119', 50  => '#bfef45',
                    60  => '#3cb44b', 70  => '#42d4f4', 80  => '#4363d8', 90  => '#911eb4',
                    100 => '#f032e6', 110 => '#a9a9a9', 120 => '#000000'
                ];
            @endphp

            @foreach([20,30,40,50,60,70,80,90,100,110,120] as $speed)
                <div class="flex items-center mb-1">
                    <span class="inline-block w-4 h-4 mr-2 rounded-sm"
                          style="background-color: {{ $colors[$speed] }};"></span>
                    {{ $speed }} km/h
                </div>
            @endforeach
        </div>
<div id="radar-log-box"
     style="font-size: 12px; max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 6px; background:#fff;">
    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
        <strong>Radar log</strong>
        <span>Fined cars: <span id="car-fines">0</span></span>
    </div>

    <!-- optional: keep basic car telemetry at top -->
    <div style="margin-bottom:4px; font-size:11px; color:#555;">
        Speed: <span id="car-speed">—</span> ·
        Heading: <span id="car-heading">—</span> ·
        Next speed change: <span id="car-distance">—</span>
    </div>

    <div id="radar-log-entries"></div>
</div>

</div>




    </div>

    <div class="p-4 border rounded shadow-sm bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
        <span id="route-info" class="font-semibold">Loading route info...</span>
    </div>

    <div class="space-y-3">
        <button onclick="toggleDirections()" class="px-4 py-2 text-white rounded shadow hover:bg-orange-600 transition" style="background-color:#ff3e00;">
            Show / Hide Directions
        </button>
        <div id="directions-box" class="hidden max-h-96 overflow-y-auto border rounded shadow-sm bg-white dark:bg-gray-800 p-4 text-gray-700 dark:text-gray-200">
            <ol id="directions-list" class="list-decimal list-inside space-y-1"></ol>
        </div>
    </div>

    <div class="overflow-x-auto border rounded mt-4">
        <table class="w-full border-collapse border table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border p-2">Drag</th>
                    <th class="border p-2">Name</th>
                    <th class="border p-2">Stop Code</th>
                    <th class="border p-2">Street</th>
                    <th class="border p-2">Municipality</th>
                </tr>
            </thead>
            <tbody id="stops-table-body" data-stops='@json($stops)'>
                @foreach($stops as $stop)
                    <tr data-id="{{ $stop->id }}" class="cursor-move">
                        <td class="border p-2 text-center">☰</td>
                        <td class="border p-2">{{ $stop->name }}</td>
                        <td class="border p-2">{{ $stop->stop_code }}</td>
                        <td class="border p-2">{{ $stop->street }}</td>
                        <td class="border p-2">{{ $stop->municipality }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ route('dashboard') }}" class="inline-block px-4 py-2 mt-4 bg-gray-300 text-gray-800 rounded shadow hover:bg-gray-400 transition">
        ← Back to Dashboard
    </a>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<!-- Page-specific JS -->
@vite('resources/js/main.js')
</x-app-layout>
