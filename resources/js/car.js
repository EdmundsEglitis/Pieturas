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
 * SVG-based car icon
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
        interactive: false
    }).addTo(map);
}

/**
 * Animate car + heading + FOV + legend
 */
export function animateCar(
    carMarker,
    latlngs,
    speedsPerSegment = [],
    speedMetersPerSecond = 15
) {
    if (!carMarker || latlngs.length < 2) return;

    const map = carMarker._map;

    let index = 0;
    let progress = 0; // meters along current segment
    let currentHeading = null;

    // ===== FOV Polygon =====
    const fovPolygon = L.polygon([], {
        color: '#000',
        weight: 2,
        opacity: 0.7,
        fillOpacity: 0.1,
        interactive: false
    }).addTo(map);

    function step() {
        if (!map) return;
        if (index >= latlngs.length - 1) return;

        const [lat1, lon1] = latlngs[index].map(Number);
        const [lat2, lon2] = latlngs[index + 1].map(Number);

        const segmentLength = map.distance(
            [lat1, lon1],
            [lat2, lon2]
        );

        if (segmentLength <= 0) {
            index++;
            progress = 0;
            requestAnimationFrame(step);
            return;
        }

        // simple stable movement (same logic as your aligned version)
        progress += speedMetersPerSecond / 60;

        if (progress >= segmentLength) {
            index++;
            progress = 0;
            requestAnimationFrame(step);
            return;
        }

        const t = progress / segmentLength;
        const [lat, lon] = interpolate(
            [lat1, lon1],
            [lat2, lon2],
            t
        );

        // === POSITION ===
        carMarker.setLatLng([lat, lon]);

        // === HEADING ===
        const targetHeading = calculateHeading(
            lat1,
            lon1,
            lat2,
            lon2
        );

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
            el.style.transform =
                `${translate} rotate(${currentHeading + MODEL_HEADING_OFFSET}deg)`;
            el.style.transformOrigin = '50% 50%';
        }

        // ===== FOV UPDATE =====
        updateFovPolygon(
            map,
            fovPolygon,
            lat,
            lon,
            currentHeading
        );

        // ===== TELEMETRY =====
        const currentSpeed = speedsPerSegment[index] ?? null;
        const distanceToNext = calculateDistanceToNextSpeedChange(
            map,
            latlngs,
            speedsPerSegment,
            index,
            t
        );

        updateCarLegend({
            speed: currentSpeed,
            heading: currentHeading,
            distanceToNextSpeed: distanceToNext
        });

        requestAnimationFrame(step);
    }

    step();
}

/**
 * Draw 100m forward-facing FOV cone
 */
function updateFovPolygon(map, polygon, lat, lon, heading) {
    const center = L.latLng(lat, lon);

    const left = projectLatLng(
        center,
        heading - FOV_HALF_ANGLE_DEG,
        FOV_DISTANCE_METERS
    );

    const right = projectLatLng(
        center,
        heading + FOV_HALF_ANGLE_DEG,
        FOV_DISTANCE_METERS
    );

    polygon.setLatLngs([
        center,
        left,
        right
    ]);
}

/**
 * Project a LatLng forward by distance (meters) and bearing (degrees)
 * Spherical-earth formula → scale-invariant
 */
function projectLatLng(origin, bearingDeg, distanceMeters) {
    const R = 6378137; // Earth radius in meters (WGS84)

    const bearing = bearingDeg * Math.PI / 180;
    const lat1 = origin.lat * Math.PI / 180;
    const lon1 = origin.lng * Math.PI / 180;

    const lat2 = Math.asin(
        Math.sin(lat1) * Math.cos(distanceMeters / R) +
        Math.cos(lat1) * Math.sin(distanceMeters / R) * Math.cos(bearing)
    );

    const lon2 =
        lon1 +
        Math.atan2(
            Math.sin(bearing) * Math.sin(distanceMeters / R) * Math.cos(lat1),
            Math.cos(distanceMeters / R) - Math.sin(lat1) * Math.sin(lat2)
        );

    return L.latLng(
        lat2 * 180 / Math.PI,
        lon2 * 180 / Math.PI
    );
}


/**
 * Project a point by heading & distance
 * Uses Leaflet's CRS so it stays aligned at all zooms
 */
function projectPoint(map, origin, bearingDeg, distanceMeters) {
    const rad = bearingDeg * Math.PI / 180;

    const offset = L.point(
        Math.sin(rad) * distanceMeters,
        -Math.cos(rad) * distanceMeters
    );

    const originPoint = map.latLngToLayerPoint(origin);
    const projected = originPoint.add(offset);

    return map.layerPointToLatLng(projected);
}

/**
 * Distance until the next speed limit change ahead
 */
function calculateDistanceToNextSpeedChange(
    map,
    latlngs,
    speeds,
    startIndex,
    t
) {
    if (!speeds.length) return 0;

    const currentSpeed = speeds[startIndex];
    if (currentSpeed == null) return 0;

    let distance = 0;

    if (startIndex < latlngs.length - 1) {
        const [aLat, aLon] = latlngs[startIndex];
        const [bLat, bLon] = latlngs[startIndex + 1];
        const segLen = map.distance(
            [aLat, aLon],
            [bLat, bLon]
        );
        distance += segLen * (1 - t);
    }

    for (
        let i = startIndex + 1;
        i < speeds.length && i < latlngs.length - 1;
        i++
    ) {
        if (speeds[i] !== currentSpeed) break;
        const [lat1, lon1] = latlngs[i];
        const [lat2, lon2] = latlngs[i + 1];
        distance += map.distance(
            [lat1, lon1],
            [lat2, lon2]
        );
    }

    return distance;
}

/**
 * Update legend UI
 */
function updateCarLegend({ speed, heading, distanceToNextSpeed }) {
    const speedEl = document.getElementById('car-speed');
    const headingEl = document.getElementById('car-heading');
    const distEl = document.getElementById('car-distance');

    if (speedEl) {
        speedEl.textContent =
            speed != null ? `${speed} km/h` : '—';
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
