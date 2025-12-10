import { addMarkers } from './stops.js';
import { getColorForSpeed, canonicalKey, formatDistance, formatTime } from './utils.js';

export async function drawRoute(map, stops, markers, polyMap, csrfToken) {
    // remove old polylines
    map.eachLayer(l => { if (l instanceof L.Polyline) map.removeLayer(l); });
    markers.forEach(m => map.removeLayer(m));
    markers.length = 0;
    polyMap.length = 0;

    const coords = stops.map(s => [s.longitude, s.latitude]);

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
        console.error('Routing error', err);
        document.getElementById('route-info').textContent = 'Routing failed';
        addMarkers(map, stops);
        return;
    }

    const latlngs = data.features[0].geometry.coordinates.map(c => [c[1], c[0]]);
    const segments = [];
    for (let i = 0; i < latlngs.length-1; i++) {
        segments.push({ lat1: latlngs[i][0], lon1: latlngs[i][1], lat2: latlngs[i+1][0], lon2: latlngs[i+1][1] });
    }

    segments.forEach(seg => {
        polyMap.push(L.polyline([[seg.lat1, seg.lon1], [seg.lat2, seg.lon2]], {
            color: 'gray', weight: 5, opacity: 0.7
        }).addTo(map));
    });

    // Fetch DB speeds and update polyMap colors (import speeds.js for Overpass logic if needed)

    addMarkers(map, stops);

    const summary = data.features[0].properties?.summary;
    if (summary) {
        document.getElementById('route-info').textContent =
            `Route: ${formatDistance(summary.distance)}, ${formatTime(summary.duration)}`;
    } else {
        document.getElementById('route-info').textContent = `Route loaded`;
    }
}
