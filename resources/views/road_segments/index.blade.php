<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Road Segments Map</h2>
    </x-slot>

    <div class="p-6 space-y-4">
        <p>Segments loaded for table: <strong>{{ $segments->count() }}</strong></p>
        <p>Segments loaded for map: <strong>{{ $segmentsForMap->count() }}</strong></p>

        <div id="map" class="w-full h-[600px] rounded border shadow"></div>

        <div class="flex space-x-2 mt-4">
            <button id="edit-selected-btn" class="px-4 py-2 bg-blue-500 text-white rounded shadow hover:bg-blue-600 transition">
                Edit Selected
            </button>
            <button id="save-changes-btn" class="px-4 py-2 bg-green-500 text-white rounded shadow hover:bg-green-600 transition">
                Save Changes
            </button>
        </div>

        <!-- Table of segments -->
        <div class="overflow-x-auto border rounded mt-4">
            <table class="w-full border-collapse border table-auto">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="border p-2">ID</th>
                        <th class="border p-2">Start</th>
                        <th class="border p-2">End</th>
                        <th class="border p-2">Max Speed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($segments as $segment)
                        <tr>
                            <td class="border p-2">{{ $segment->id }}</td>
                            <td class="border p-2">{{ $segment->lat1 }}, {{ $segment->lon1 }}</td>
                            <td class="border p-2">{{ $segment->lat2 }}, {{ $segment->lon2 }}</td>
                            <td class="border p-2">{{ $segment->maxspeed }} km/h</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $segments->links() }}
    </div>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const speedColors = {20:'red',30:'pink',50:'orange',70:'lime',80:'yellow',90:'green',100:'teal',110:'blue',120:'purple'};
        function getColorForSpeed(speed){return speedColors[speed] ?? 'gray';}

        let segments = @json($segmentsForMap ?? []);
        let changedSegments = {}; // id -> new speed
        let selectedSegments = new Set(); // IDs of currently selected segments

        if (!Array.isArray(segments) || segments.length === 0) {
            console.warn("No valid segments to display on map.");
        } else {
            const map = L.map('map').fitBounds(
                segments.map(s => [parseFloat(s.lat1), parseFloat(s.lon1)])
            );

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            const segmentLayers = {};

            segments.forEach(s => {
                if (!s.lat1 || !s.lon1 || !s.lat2 || !s.lon2) return;

                const color = getColorForSpeed(s.maxspeed);

                const line = L.polyline(
                    [
                        [parseFloat(s.lat1), parseFloat(s.lon1)],
                        [parseFloat(s.lat2), parseFloat(s.lon2)]
                    ],
                    {
                        color,
                        weight: 8,
                        opacity: 0.6,
                        interactive: true
                    }
                ).addTo(map);

                segmentLayers[s.id] = line;

                // Single click to edit
                line.on('click', () => {
                    const currentSpeed = changedSegments[s.id] ?? s.maxspeed;
                    const newSpeed = prompt(`Change max speed for segment ${s.id}:`, currentSpeed);
                    const parsed = parseInt(newSpeed);
                    if (!isNaN(parsed) && parsed > 0) {
                        changedSegments[s.id] = parsed;
                        line.setStyle({color: getColorForSpeed(parsed), opacity:0.9});
                        selectedSegments.add(s.id);
                    }
                });

                // Right-click to select multiple
                line.on('contextmenu', (e) => {
                    e.originalEvent.preventDefault();
                    if(selectedSegments.has(s.id)){
                        selectedSegments.delete(s.id);
                        line.setStyle({opacity:0.6, color: getColorForSpeed(changedSegments[s.id] ?? s.maxspeed)});
                    } else {
                        selectedSegments.add(s.id);
                        line.setStyle({opacity:0.9, color: 'cyan'});
                    }
                });

                line.bindPopup(`Max Speed: ${s.maxspeed} km/h`);
            });

            // --- Shift-drag selection ---
            let selectionRect = null;
            let startPoint = null;

            function getMouseLatLng(evt){ return map.containerPointToLatLng(evt.point); }

            map.on('mousedown', function(e){
                if(!e.originalEvent.shiftKey) return;
                startPoint = e.containerPoint;
                if(selectionRect) map.removeLayer(selectionRect);
                selectionRect = L.rectangle([map.containerPointToLatLng(startPoint), map.containerPointToLatLng(startPoint)], {
                    color:'cyan', fill:true, fillOpacity:0.1
                }).addTo(map);
            });

            map.on('mousemove', function(e){
                if(!startPoint || !e.originalEvent.shiftKey) return;
                selectionRect.setBounds(L.latLngBounds(map.containerPointToLatLng(startPoint), map.containerPointToLatLng(e.containerPoint)));
            });

            map.on('mouseup', function(e){
                if(!startPoint || !selectionRect) return;
                const bounds = selectionRect.getBounds();
                for(const [id, line] of Object.entries(segmentLayers)){
                    const latlngs = line.getLatLngs();
                    if(bounds.contains(latlngs[0]) || bounds.contains(latlngs[1])){
                        selectedSegments.add(parseInt(id));
                        line.setStyle({color:'cyan', opacity:0.9});
                    }
                }
                map.removeLayer(selectionRect);
                selectionRect = null;
                startPoint = null;
            });

            // --- Edit selected button ---
            document.getElementById('edit-selected-btn').addEventListener('click', ()=>{
                if(selectedSegments.size === 0){
                    alert("No segments selected.");
                    return;
                }
                const newSpeed = prompt("Set new speed for all selected segments:");
                const parsed = parseInt(newSpeed);
                if(isNaN(parsed) || parsed <= 0) return;
                selectedSegments.forEach(id => {
                    changedSegments[id] = parsed;
                    const line = segmentLayers[id];
                    line.setStyle({color:'cyan', opacity:0.9});
                });
            });

            // --- Save all changes button ---
            document.getElementById('save-changes-btn').addEventListener('click', async () => {
                const toSave = Object.entries(changedSegments).map(([id, maxspeed]) => ({id: parseInt(id), maxspeed}));
                if(toSave.length === 0){
                    alert("No changes to save.");
                    return;
                }

                try {
                    const resp = await fetch('/api/save-road-segment-batch-admin', { // <- renamed for admin
                        method: 'POST',
                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
                        body: JSON.stringify({segments: toSave})
                    });
                    if(resp.ok){
                        alert("Changes saved successfully!");
                        changedSegments = {};
                        selectedSegments.clear();
                        toSave.forEach(seg => {
                            segmentLayers[seg.id].setStyle({opacity:0.6, color: getColorForSpeed(seg.maxspeed)});
                        });
                    } else {
                        alert("Failed to save changes.");
                    }
                } catch(err){
                    console.error("Save error", err);
                    alert("Error saving changes.");
                }
            });
        }
    </script>
</x-app-layout>
