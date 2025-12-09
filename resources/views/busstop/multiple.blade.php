<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Selected Bus Stops</h2>
    </x-slot>

    <div class="p-6 space-y-6">

        <p class="text-gray-700 dark:text-gray-300">
            You have selected <strong>{{ count($stops) }}</strong> bus stop(s).
        </p>

        {{-- Map --}}
        <div class="relative">
            <div id="map" class="w-full h-[600px] rounded border shadow"></div>

            {{-- Speed Legend --}}
            <div class="p-2 rounded bg-white shadow absolute top-4 right-4 z-[9999] text-sm">
                <h4 class="font-bold mb-1">Speed Limit (Latvia)</h4>
                <div><span class="inline-block w-4 h-4 bg-red-500 mr-1"></span> 20</div>
                <div><span class="inline-block w-4 h-4 bg-pink-500 mr-1"></span> 30</div>
                <div><span class="inline-block w-4 h-4 bg-orange-500 mr-1"></span> 50</div>
                <div><span class="inline-block w-4 h-4 bg-lime-500 mr-1"></span> 70</div>
                <div><span class="inline-block w-4 h-4 bg-yellow-400 mr-1"></span> 80</div>
                <div><span class="inline-block w-4 h-4 bg-green-500 mr-1"></span> 90</div>
                <div><span class="inline-block w-4 h-4 bg-teal-500 mr-1"></span> 100</div>
                <div><span class="inline-block w-4 h-4 bg-blue-500 mr-1"></span> 110</div>
                <div><span class="inline-block w-4 h-4 bg-purple-500 mr-1"></span> 120</div>
            </div>
        </div>

        {{-- Route Summary --}}
        <div class="p-4 border rounded shadow-sm bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
            <span id="route-info" class="font-semibold">Loading route info...</span>
        </div>

        {{-- Directions --}}
        <div class="space-y-3">
            <button
                onclick="toggleDirections()"
                class="px-4 py-2 text-white rounded shadow hover:bg-orange-600 transition"
                style="background-color: #ff3e00;">
                Show / Hide Directions
            </button>

            <div id="directions-box" class="hidden max-h-96 overflow-y-auto border rounded shadow-sm bg-white dark:bg-gray-800 p-4 text-gray-700 dark:text-gray-200">
                <ol id="directions-list" class="list-decimal list-inside space-y-1"></ol>
            </div>
        </div>

        {{-- Draggable Table --}}
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
                <tbody id="stops-table-body">
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

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Leaflet + SortableJS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function toggleDirections() {
            document.getElementById('directions-box').classList.toggle('hidden');
        }

        function formatDistance(m) {
            return m < 1000 ? `${m.toFixed(0)} m` : `${(m/1000).toFixed(2)} km`;
        }

        function formatTime(s) {
            const h = Math.floor(s / 3600),
                  m = Math.floor((s % 3600) / 60);
            return h > 0 ? `${h} h ${m} min` : `${m} min`;
        }

        function getColorForSpeed(speed) {
            switch(speed) {
                case 20: return 'red';
                case 30: return 'pink';
                case 50: return 'orange';
                case 70: return 'lime';
                case 80: return 'yellow';
                case 90: return 'green';
                case 100: return 'teal';
                case 110: return 'blue';
                case 120: return 'purple';
                default: return 'gray';
            }
        }

        async function fetchAllMaxspeeds(bounds) {
            const [south, west] = [bounds.getSouth(), bounds.getWest()];
            const [north, east] = [bounds.getNorth(), bounds.getEast()];
            const query = `[out:json];way["highway"]["maxspeed"](${south},${west},${north},${east});out tags geom;`;
            const url = 'https://overpass-api.de/api/interpreter?data=' + encodeURIComponent(query);

            try {
                const resp = await fetch(url);
                const text = await resp.text();

                // If response is not JSON, return empty array
                if (!text.startsWith('{')) {
                    console.warn('Overpass returned non-JSON response');
                    return [];
                }

                const data = JSON.parse(text);
                return data.elements || [];
            } catch (err) {
                console.warn('Overpass error', err);
                return [];
            }
        }

        function getSegmentSpeed(lat1, lon1, lat2, lon2, ways) {
            let nearestSpeed = null;
            let minDist = Infinity;

            ways.forEach(w => {
                if (!w.geometry) return;
                for (let i = 0; i < w.geometry.length - 1; i++) {
                    const pt1 = w.geometry[i];
                    const pt2 = w.geometry[i+1];
                    const midSegLat = (lat1 + lat2) / 2;
                    const midSegLon = (lon1 + lon2) / 2;
                    const dist = map.distance([midSegLat, midSegLon], [(pt1.lat + pt2.lat)/2, (pt1.lon + pt2.lon)/2]);

                    if (dist < minDist) {
                        minDist = dist;
                        nearestSpeed = parseInt(w.tags.maxspeed) || null;
                    }
                }
            });

            return nearestSpeed || 50;
        }

        document.addEventListener('DOMContentLoaded', async function () {
            let stops = @json($stops);
            if (!stops.length) return;

            const mapStops = stops.map(s => L.latLng(s.latitude, s.longitude));
            window.map = L.map('map').fitBounds(mapStops);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            let orderedStops = [...stops];
            let markers = [];

            async function drawRoute() {
                map.eachLayer(l => { if (l instanceof L.Polyline) map.removeLayer(l); });
                markers.forEach(m => map.removeLayer(m));
                markers = [];

                const coords = orderedStops.map(s => [s.longitude, s.latitude]);

                let data;
                try {
                    const res = await fetch("https://api.openrouteservice.org/v2/directions/driving-car/geojson", {
                        method: "POST",
                        headers: {
                            "Authorization": "{{ config('services.openrouteservice.key') }}",
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({ coordinates: coords, instructions: true })
                    });
                    data = await res.json();
                } catch (err) {
                    console.error(err);
                    document.getElementById('route-info').textContent = 'Routing failed';
                    return;
                }

                const routeCoords = data.features[0].geometry.coordinates;
                const latlngs = routeCoords.map(c => [c[1], c[0]]);

                // Fetch Overpass for bounding box (keeps your fallback, unchanged)
                const allMaxspeeds = await fetchAllMaxspeeds(map.getBounds());

                // Prepare segments
                const segments = [];
                for (let i = 0; i < latlngs.length - 1; i++) {
                    segments.push({
                        lat1: latlngs[i][0], lon1: latlngs[i][1],
                        lat2: latlngs[i+1][0], lon2: latlngs[i+1][1]
                    });
                }

                // Batch fetch from DB (returns [{index, maxspeed}, ...])
                let dbSegments = [];
                try {
                    const resp = await fetch('/api/road-segments-batch', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ segments })
                    });
                    dbSegments = await resp.json();
                } catch(err) {
                    console.error('Failed to fetch DB segments', err);
                }

                // Build mapping by index for safety
                const dbByIndex = {};
                if (Array.isArray(dbSegments)) {
                    dbSegments.forEach(item => {
                        if (typeof item.index !== 'undefined') {
                            dbByIndex[item.index] = item;
                        }
                    });
                }

                // Collect missing segments to save in one batch
                const missingSegments = [];

                for (let i = 0; i < latlngs.length - 1; i++) {
                    const lat1 = latlngs[i][0], lon1 = latlngs[i][1];
                    const lat2 = latlngs[i+1][0], lon2 = latlngs[i+1][1];

                    let dbEntry = dbByIndex[i];
                    let speed = dbEntry?.maxspeed;

                    if (!speed) {
                        // Fallback: Overpass nearest-match (as before)
                        speed = getSegmentSpeed(lat1, lon1, lat2, lon2, allMaxspeeds);

                        // Collect to batch-save later (one HTTP request)
                        missingSegments.push({ lat1, lon1, lat2, lon2, maxspeed: speed });
                    }

                    const color = getColorForSpeed(speed);
                    L.polyline([[lat1, lon1], [lat2, lon2]], {color, weight:5, opacity:0.85}).addTo(map);
                }

                // Save missing segments in a single batched POST (if any)
                if (missingSegments.length > 0) {
                    try {
                        fetch('/api/save-road-segment-batch', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ segments: missingSegments })
                        })
                        // intentionally not awaiting to avoid blocking UI; you may await if desired
                        .catch(err => console.warn('Failed to save batch segments', err));
                    } catch (err) {
                        console.warn('Save batch error', err);
                    }
                }

                // Add draggable markers
                orderedStops.forEach(stop => {
                    const m = L.marker([stop.latitude, stop.longitude], { draggable: true })
                        .bindPopup(stop.name)
                        .addTo(map)
                        .on('dragend', e => {
                            stop.latitude = e.target.getLatLng().lat;
                            stop.longitude = e.target.getLatLng().lng;
                            drawRoute();
                        });
                    markers.push(m);
                });

                // Route summary
                const summary = data.features[0].properties.summary;
                document.getElementById('route-info').textContent =
                    `Route: ${formatDistance(summary.distance)}, ${formatTime(summary.duration)}`;

                // Directions
                const steps = data.features[0].properties.segments[0].steps;
                const dirList = document.getElementById('directions-list');
                dirList.innerHTML = '';
                steps.forEach(step => {
                    const li = document.createElement('li');
                    li.textContent = `${step.instruction} — ${formatDistance(step.distance)}, ${formatTime(step.duration)}`;
                    dirList.appendChild(li);
                });
            }

            drawRoute();

            // Sortable table
            Sortable.create(document.getElementById('stops-table-body'), {
                animation: 150,
                onEnd: function() {
                    const newOrder = Array.from(document.querySelectorAll('#stops-table-body tr')).map(tr => {
                        const id = parseInt(tr.dataset.id);
                        return stops.find(s => s.id === id);
                    });
                    orderedStops = newOrder;
                    drawRoute();
                }
            });
        });
    </script>
</x-app-layout>
