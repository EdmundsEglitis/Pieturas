// resources/js/simulation/dummyCars.js
import L from 'leaflet';
import { interpolate } from '../utils.js';

function createDummyCarIcon() {
    return L.divIcon({
        className: 'dummy-car-icon',
        html: `
            <div style="
                width: 16px;
                height: 8px;
                background: #555;
                border-radius: 2px;
                border: 1px solid #222;
            "></div>
        `,
        iconSize: [16, 8],
        iconAnchor: [8, 4],
    });
}

// global handle to cancel the current animation loop
let dummyCarsAnimationFrameId = null;

/**
 * Spawn dummy cars moving opposite direction along the route.
 * @param {L.Map} map
 * @param {Array<[number,number]>} latlngs
 * @param {number} count
 * @returns {Array<Object>} dummyCars
 */
export function spawnDummyCars(map, latlngs, count = 5) {
    if (!latlngs || latlngs.length < 2) return [];

    const dummyCars = [];
    let nextId = 1;

    for (let i = 0; i < count; i++) {
        const startSegment = latlngs.length - 2;
        const [lat, lon] = latlngs[startSegment + 1];

        const marker = L.marker([lat, lon], {
            icon: createDummyCarIcon(),
            interactive: false,
        }).addTo(map);

        // 50–100 km/h test traffic (you can change range)
        const speedKmh = 45 ;

        dummyCars.push({
            id: nextId++,
            marker,
            speedKmh,
            segmentIndex: startSegment,
            distanceAlongSegment: i * 20, // stagger ~20m apart
        });
    }

    animateDummyCars(map, latlngs, dummyCars);

    return dummyCars;
}

/**
 * Stop animation and remove dummy car markers from map
 */
export function destroyDummyCars(map, dummyCars) {
    if (dummyCarsAnimationFrameId !== null) {
        cancelAnimationFrame(dummyCarsAnimationFrameId);
        dummyCarsAnimationFrameId = null;
    }

    if (!dummyCars) return;

    dummyCars.forEach((car) => {
        if (car.marker && map && map.hasLayer(car.marker)) {
            map.removeLayer(car.marker);
        }
    });
}

/**
 * Animate dummy cars backward along the route
 */
function animateDummyCars(map, latlngs, dummyCars) {
    if (!dummyCars.length) return;

    let lastTime = performance.now();

    function step(now) {
        const dt = Math.max((now - lastTime) / 1000, 0.016);
        lastTime = now;

        dummyCars.forEach((car) => {
            const speedMS = car.speedKmh / 3.6;

            let idx = car.segmentIndex;
            let dist = car.distanceAlongSegment;

            // moving from end → start of route
            const start = latlngs[idx + 1];
            const end = latlngs[idx];

            const segLen = map.distance(start, end);
            if (segLen <= 0) {
                idx = latlngs.length - 2;
                dist = 0;
            }

            dist += speedMS * dt;

            while (dist >= segLen) {
                dist -= segLen;
                idx--;
                if (idx < 0) {
                    idx = latlngs.length - 2;
                }
            }

            const t = segLen ? dist / segLen : 0;
            const [lat, lon] = interpolate(
                [start.lat ?? start[0], start.lng ?? start[1]],
                [end.lat ?? end[0], end.lng ?? end[1]],
                t,
            );

            car.segmentIndex = idx;
            car.distanceAlongSegment = dist;
            car.marker.setLatLng([lat, lon]);
        });

        dummyCarsAnimationFrameId = requestAnimationFrame(step);
    }

    dummyCarsAnimationFrameId = requestAnimationFrame(step);
}
