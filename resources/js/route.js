import { getColorForSpeed } from './speeds.js';
import { canonicalizeSegment, formatDistance, formatTime } from './utils.js';

export async function drawRoute(map, stops, csrfToken) {
    if (!stops.length) return { latlngs: [], speedsPerSegment: [] };

    // clear old polylines & markers
    map.eachLayer(l => {
        if (l instanceof L.Polyline || l instanceof L.Marker) {
            map.removeLayer(l);
        }
    });

    const coords = stops.map(s => [s.longitude, s.latitude]);

    // ================= ORS =================
    const res = await fetch('/api/route', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ coordinates: coords, instructions: true })
    });

    if (!res.ok) {
        document.getElementById('route-info').textContent = 'Routing failed';
        return { latlngs: [], speedsPerSegment: [] };
    }

    const data = await res.json();

    const latlngs = data.features[0].geometry.coordinates
        .map(c => [Number(c[1]), Number(c[0])]);

    // ================= SEGMENTS =================
    const segments = [];
    for (let i = 0; i < latlngs.length - 1; i++) {
        segments.push({
            lat1: latlngs[i][0],
            lon1: latlngs[i][1],
            lat2: latlngs[i + 1][0],
            lon2: latlngs[i + 1][1]
        });
    }

    const polyMap = segments.map(seg =>
        L.polyline(
            [[seg.lat1, seg.lon1], [seg.lat2, seg.lon2]],
            { color: '#999', weight: 5, opacity: 0.7 }
        ).addTo(map)
    );

    // ================= DB LOOKUP =================
    const dbResp = await fetch('/api/road-segments-batch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ segments })
    });

    const dbSegments = await dbResp.json();
    const speedsPerSegment = Array(segments.length).fill(null);

    const missingIndexes = [];

    dbSegments.forEach((row, i) => {
        if (row?.maxspeed) {
            speedsPerSegment[i] = row.maxspeed;
            polyMap[i].setStyle({ color: getColorForSpeed(row.maxspeed) });
        } else {
            missingIndexes.push(i);
        }
    });

    // ================= OVERPASS FALLBACK =================
    if (missingIndexes.length) {
        const bbox = missingIndexes.reduce(
            (b, i) => {
                const s = segments[i];
                return {
                    south: Math.min(b.south, s.lat1, s.lat2),
                    north: Math.max(b.north, s.lat1, s.lat2),
                    west: Math.min(b.west, s.lon1, s.lon2),
                    east: Math.max(b.east, s.lon1, s.lon2),
                };
            },
            { south: 90, north: -90, west: 180, east: -180 }
        );

        const query = `
[out:json];
way["highway"]["maxspeed"](${bbox.south},${bbox.west},${bbox.north},${bbox.east});
out tags geom;
`;

        let ways = [];
        try {
            const r = await fetch(
                'https://overpass-api.de/api/interpreter?data=' + encodeURIComponent(query)
            );
            ways = (await r.json()).elements || [];
        } catch (e) {
            console.warn('Overpass failed', e);
        }

        const toSave = [];

        missingIndexes.forEach(idx => {
            const seg = segments[idx];
            const midLat = (seg.lat1 + seg.lat2) / 2;
            const midLon = (seg.lon1 + seg.lon2) / 2;

            let best = null;
            let bestDist = Infinity;

            ways.forEach(w => {
                if (!w.geometry || !w.tags?.maxspeed) return;

                for (let i = 0; i < w.geometry.length - 1; i++) {
                    const a = w.geometry[i];
                    const b = w.geometry[i + 1];
                    const d = map.distance(
                        [midLat, midLon],
                        [(a.lat + b.lat) / 2, (a.lon + b.lon) / 2]
                    );
                    if (d < bestDist) {
                        bestDist = d;
                        best = w;
                    }
                }
            });

            if (best) {
                const speed = parseInt(best.tags.maxspeed, 10);
                if (!isNaN(speed)) {
                    speedsPerSegment[idx] = speed;
                    polyMap[idx].setStyle({ color: getColorForSpeed(speed) });
                    toSave.push({ ...seg, maxspeed: speed });
                }
            }
        });

        // ================= SAVE BACK TO DB =================
        if (toSave.length) {
            await fetch('/api/save-road-segment-batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ segments: toSave })
            });
        }
    }

    // ================= INFO + RETURN =================
    const summary = data.features[0].properties?.summary;
    if (summary) {
        document.getElementById('route-info').textContent =
            `Route: ${formatDistance(summary.distance)}, ${formatTime(summary.duration)}`;
    }

    return { latlngs, speedsPerSegment };
}
