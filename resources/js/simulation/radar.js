// resources/js/simulation/radar.js
import { destinationPoint } from '../utils.js';

const RADAR_DISTANCE = 100; // meters
const RADAR_WIDTH = 6;      // meters

export function computeRadarPoint(position, heading) {
    return destinationPoint(
        position[0],
        position[1],
        heading,
        RADAR_DISTANCE
    );
}

export function detectCars({
    map,
    police,
    dummyCars,
    speedsPerSegment
}) {
    const radarPoint = computeRadarPoint(
        police.position,
        police.heading
    );

    const speedLimit = speedsPerSegment[police.segmentIndex] ?? null;

    return dummyCars
        .filter(car => {
            const d = map.distance(
                radarPoint,
                car.marker.getLatLng()
            );
            return d <= RADAR_WIDTH;
        })
        .map(car => ({
            car,
            speedLimit
        }));
}
