// resources/js/car.js
import L from 'leaflet';
import { calculateHeading, interpolate } from './utils.js';

/**
 * Your SVG car points EAST by default.
 * Heading calculation is NORTH-based → rotate by +90°
 */
const MODEL_HEADING_OFFSET = 90;

// ===== FOV CONFIG =====
const FOV_DISTANCE_METERS = 100;
const FOV_HALF_ANGLE_DEG = 22;

/**
 * SVG-based police car icon
 */
export function createCarIcon() {
    return L.divIcon({
        className: 'car-icon',
        html: `
            <img
                src="/police.png"
                style="width:28px;height:14px;display:block;"
            />
        `,
        iconSize: [28, 14],
        iconAnchor: [14, 7], // exact visual center
    });
}

export function placeCar(map, lat, lon) {
    return L.marker([Number(lat), Number(lon)], {
        icon: createCarIcon(),
        interactive: false,
    }).addTo(map);
}

/**
 * Animate car + heading + FOV + legend
 *
 * @param {L.Marker} carMarker
 * @param {Array<[number,number]>} latlngs - route coords from ORS
 * @param {Array<number|null>} speedsPerSegment - maxspeed per segment
 * @param {number} speedMetersPerSecond - visual animation speed
 * @param {function} onUpdate - optional callback:
 *        ({ lat, lon, heading, segmentIndex, t, speedLimitKmh }) => void
 *
 * @returns {function} cancelAnimation - call to stop the animation loop
 */
export function animateCar(
    carMarker,
    latlngs,
    speedsPerSegment = [],
    speedMetersPerSecond = 15,
    onUpdate,
) {
    if (!carMarker || latlngs.length < 2) return () => {};

    const map = carMarker._map;
    if (!map) return () => {};

    let index = 0;
    let progress = 0; // meters along current segment
    let currentHeading = null;
    let animationFrameId = null;

    // ===== FOV Polygon (100 m cone ahead) =====
    const fovPolygon = L.polygon([], {
        color: '#000',
        weight: 2,
        opacity: 0.7,
        fillOpacity: 0.1,
        interactive: false,
    }).addTo(map);

    function step() {
        if (!map) return;

        // stop if we've reached the end of the route
        if (index >= latlngs.length - 1) return;

        const [lat1, lon1] = latlngs[index].map(Number);
        const [lat2, lon2] = latlngs[index + 1].map(Number);

        const segmentLength = map.distance([lat1, lon1], [lat2, lon2]);

        if (segmentLength <= 0) {
            index++;
            progress = 0;
            animationFrameId = requestAnimationFrame(step);
            return;
        }

        // simple 60fps-style movement
        progress += speedMetersPerSecond / 60;

        if (progress >= segmentLength) {
            index++;
            progress = 0;
            animationFrameId = requestAnimationFrame(step);
            return;
        }

        const t = progress / segmentLength;
        const [lat, lon] = interpolate([lat1, lon1], [lat2, lon2], t);

        // === POSITION ===
        carMarker.setLatLng([lat, lon]);

        // === HEADING ===
        const targetHeading = calculateHeading(lat1, lon1, lat2, lon2);

        if (currentHeading === null) {
            currentHeading = targetHeading;
        }

        let diff = targetHeading - currentHeading;
        if (diff > 180) diff -= 360;
        if (diff < -180) diff += 360;
        currentHeading += diff * 0.15;

        const el = carMarker.getElement();
        if (el) {
            const translate =
                el.style.transform.match(/translate3d\([^)]+\)/)?.[0] || '';
            el.style.transform = `${translate} rotate(${
                currentHeading + MODEL_HEADING_OFFSET
            }deg)`;
            el.style.transformOrigin = '50% 50%';
        }

        // ===== FOV UPDATE =====
        updateFovPolygon(map, fovPolygon, lat, lon, currentHeading);

        // ===== TELEMETRY FOR LEGEND =====
        const currentSpeedLimit = speedsPerSegment[index] ?? null;
        const distanceToNext = calculateDistanceToNextSpeedChange(
            map,
            latlngs,
            speedsPerSegment,
            index,
            t,
        );

        updateCarLegend({
            speed: currentSpeedLimit,
            heading: currentHeading,
            distanceToNextSpeed: distanceToNext,
        });

        // ===== CALLBACK FOR RADAR / SIMULATION =====
        if (typeof onUpdate === 'function') {
            onUpdate({
                lat,
                lon,
                heading: currentHeading,
                segmentIndex: index,
                t,
                speedLimitKmh: currentSpeedLimit,
            });
        }

        animationFrameId = requestAnimationFrame(step);
    }

    animationFrameId = requestAnimationFrame(step);

    // Return a cancel function so we can stop this loop on route rebuild
    return () => {
        if (animationFrameId !== null) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
        // also remove FOV from the map
        if (map.hasLayer(fovPolygon)) {
            map.removeLayer(fovPolygon);
        }
    };
}

/**
 * Draw 100m forward-facing FOV cone
 * Scale-invariant using spherical projection
 */
function updateFovPolygon(map, polygon, lat, lon, heading) {
    const center = L.latLng(lat, lon);

    const left = projectLatLng(
        center,
        heading - FOV_HALF_ANGLE_DEG,
        FOV_DISTANCE_METERS,
    );

    const right = projectLatLng(
        center,
        heading + FOV_HALF_ANGLE_DEG,
        FOV_DISTANCE_METERS,
    );

    polygon.setLatLngs([center, left, right]);
}

/**
 * Project a LatLng forward by distance (meters) and bearing (degrees)
 * Spherical-earth formula → independent of zoom
 */
function projectLatLng(origin, bearingDeg, distanceMeters) {
    const R = 6378137; // Earth radius in meters (WGS84)

    const bearing = (bearingDeg * Math.PI) / 180;
    const lat1 = (origin.lat * Math.PI) / 180;
    const lon1 = (origin.lng * Math.PI) / 180;

    const lat2 = Math.asin(
        Math.sin(lat1) * Math.cos(distanceMeters / R) +
            Math.cos(lat1) *
                Math.sin(distanceMeters / R) *
                Math.cos(bearing),
    );

    const lon2 =
        lon1 +
        Math.atan2(
            Math.sin(bearing) *
                Math.sin(distanceMeters / R) *
                Math.cos(lat1),
            Math.cos(distanceMeters / R) -
                Math.sin(lat1) * Math.sin(lat2),
        );

    return L.latLng((lat2 * 180) / Math.PI, (lon2 * 180) / Math.PI);
}

/**
 * Distance until the next speed limit change ahead
 */
function calculateDistanceToNextSpeedChange(
    map,
    latlngs,
    speeds,
    startIndex,
    t,
) {
    if (!speeds || !speeds.length) return 0;

    const currentSpeed = speeds[startIndex];
    if (currentSpeed == null) return 0;

    let distance = 0;

    // remaining part of current segment
    if (startIndex < latlngs.length - 1) {
        const [aLat, aLon] = latlngs[startIndex];
        const [bLat, bLon] = latlngs[startIndex + 1];
        const segLen = map.distance([aLat, aLon], [bLat, bLon]);
        distance += segLen * (1 - t);
    }

    // subsequent segments with same speed
    for (
        let i = startIndex + 1;
        i < speeds.length && i < latlngs.length - 1;
        i++
    ) {
        if (speeds[i] !== currentSpeed) break;

        const [lat1, lon1] = latlngs[i];
        const [lat2, lon2] = latlngs[i + 1];
        distance += map.distance([lat1, lon1], [lat2, lon2]);
    }

    return distance;
}

/**
 * Update the police car legend UI
 * Expects elements with ids: car-speed, car-heading, car-distance
 */
function updateCarLegend({ speed, heading, distanceToNextSpeed }) {
    const speedEl = document.getElementById('car-speed');
    const headingEl = document.getElementById('car-heading');
    const distEl = document.getElementById('car-distance');

    if (speedEl) {
        speedEl.textContent = speed != null ? `${speed} km/h` : '—';
    }

    if (headingEl && heading != null) {
        headingEl.textContent = `${heading.toFixed(0)}°`;
    }

    if (distEl && distanceToNextSpeed != null) {
        distEl.textContent =
            distanceToNextSpeed >= 1000
                ? `${(distanceToNextSpeed / 1000).toFixed(2)} km`
                : `${distanceToNextSpeed.toFixed(0)} m`;
    }
}
