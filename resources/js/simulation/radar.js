// resources/js/simulation/radar.js
import L from 'leaflet';
import { calculateHeading } from '../utils.js';

// Keep in sync with car.js FOV config
const RADAR_DISTANCE = 100; // meters
const RADAR_HALF_ANGLE_DEG = 22; // degrees
const SPEED_TOLERANCE_KMH = 5; // allowed over-limit buffer

/**
 * Find nearest route segment to a point, in meters.
 * @param {L.Map} map
 * @param {Array<[number,number]>} latlngs
 * @param {L.LatLng} pointLatLng
 * @returns {{index:number, distance:number}}
 */
function findNearestSegmentIndex(map, latlngs, pointLatLng) {
    let nearestIndex = -1;
    let nearestDistance = Infinity;

    const p = map.latLngToLayerPoint(pointLatLng);

    for (let i = 0; i < latlngs.length - 1; i++) {
        const aLatLng = L.latLng(latlngs[i][0], latlngs[i][1]);
        const bLatLng = L.latLng(latlngs[i + 1][0], latlngs[i + 1][1]);

        const a = map.latLngToLayerPoint(aLatLng);
        const b = map.latLngToLayerPoint(bLatLng);

        const abx = b.x - a.x;
        const aby = b.y - a.y;
        const apx = p.x - a.x;
        const apy = p.y - a.y;

        const abLen2 = abx * abx + aby * aby;
        let t = 0;
        if (abLen2 > 0) {
            t = (apx * abx + apy * aby) / abLen2;
            t = Math.max(0, Math.min(1, t));
        }

        const closestPoint = L.point(a.x + abx * t, a.y + aby * t);
        const closestLatLng = map.layerPointToLatLng(closestPoint);

        const dist = map.distance(pointLatLng, closestLatLng);

        if (dist < nearestDistance) {
            nearestDistance = dist;
            nearestIndex = i;
        }
    }

    return { index: nearestIndex, distance: nearestDistance };
}

/**
 * Detect offenders in the police car's FOV
 *
 * @param {L.Map} map
 * @param {{lat:number,lon:number,heading:number,segmentIndex?:number}} policeState
 * @param {Array<Object>} dummyCars
 * @param {Array<[number,number]>} latlngs
 * @param {Array<number|null>} speedsPerSegment
 * @returns {Array<Object>} detections
 */
export function detectOffenders(
    map,
    policeState,
    dummyCars,
    latlngs,
    speedsPerSegment,
) {
    if (!policeState || !dummyCars || !dummyCars.length) return [];
    if (!latlngs || latlngs.length < 2) return [];

    const { lat, lon, heading } = policeState;

    const detections = [];

    dummyCars.forEach((car) => {
        const marker = car.marker;
        const mapRef = marker?._map || map;
        if (!mapRef) return;

        const pos = marker.getLatLng();

        // Distance check (real meters)
        const distance = mapRef.distance(
            [lat, lon],
            [pos.lat, pos.lng],
        );
        if (distance > RADAR_DISTANCE) return;

        // FOV angle check
        const bearingToCar = calculateHeading(
            lat,
            lon,
            pos.lat,
            pos.lng,
        );

        let diff = bearingToCar - heading;
        if (diff > 180) diff -= 360;
        if (diff < -180) diff += 360;

        if (Math.abs(diff) > RADAR_HALF_ANGLE_DEG) return;

        // Determine speed limit at offender location (NOT at police car)
        const { index: segIndex } = findNearestSegmentIndex(
            mapRef,
            latlngs,
            pos,
        );
        if (segIndex == null || segIndex < 0) return;

        const limitKmhRaw = speedsPerSegment[segIndex];
        if (limitKmhRaw == null) {
            // unknown speed limit → do NOT fine
            return;
        }

        const limitKmh = Number(limitKmhRaw);
        const overBy = car.speedKmh - limitKmh;
        const isOffender = overBy > SPEED_TOLERANCE_KMH;
        if (!isOffender) return;

        detections.push({
            car,
            distance,
            position: { lat: pos.lat, lng: pos.lng },
            limitKmh,
            speedKmh: car.speedKmh,
            overBy,
        });
    });

    return detections;
}
