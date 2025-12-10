// resources/js/main.js
import { initMap } from './map.js';
import { drawRoute } from './route.js';
import { initStopsTable } from './stops.js';
import { placeCar, animateCar } from './car.js';

import {
    spawnDummyCars,
    destroyDummyCars,
} from './simulation/dummyCars.js';
import { detectOffenders } from './simulation/radar.js';
import {
    processDetections,
    resetEnforcement,
} from './simulation/enforcement.js';
import {
    updateFinesLegend,
    appendRadarLogEntry,
    clearRadarLog,
} from './simulation/uiLegend.js';

import { toggleDirections } from './utils.js';

// For the blade button: onclick="toggleDirections()"
window.toggleDirections = toggleDirections;

document.addEventListener('DOMContentLoaded', async () => {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute('content');

    const tbody = document.getElementById('stops-table-body');
    const stops = tbody ? JSON.parse(tbody.dataset.stops) : [];
    if (!stops.length) return;

    const map = initMap(stops);

    let carMarker = null;
    let dummyCars = [];
    let lastRouteResult = null;
    let stopCarAnimation = null; // cancel function from animateCar

    async function rebuildRoute(orderedStops = stops) {
        // ----- clear previous simulation state -----
        resetEnforcement();
        clearRadarLog();
        updateFinesLegend(0);

        // stop previous police car animation
        if (typeof stopCarAnimation === 'function') {
            stopCarAnimation();
            stopCarAnimation = null;
        }

        // remove previous dummy cars and stop their animation
        if (dummyCars.length) {
            destroyDummyCars(map, dummyCars);
            dummyCars = [];
        }

        // remove previous marker
        if (carMarker) {
            map.removeLayer(carMarker);
            carMarker = null;
        }

        // ----- get new route -----
        lastRouteResult = await drawRoute(map, orderedStops, csrfToken);
        if (
            !lastRouteResult ||
            !lastRouteResult.latlngs ||
            !lastRouteResult.latlngs.length
        ) {
            return;
        }

        const { latlngs, speedsPerSegment } = lastRouteResult;

        // Place police car at the start of the route
        carMarker = placeCar(map, latlngs[0][0], latlngs[0][1]);

        // Spawn dummy cars for THIS route
        dummyCars = spawnDummyCars(map, latlngs, 6); // 6 test cars

        // Called every frame from animateCar
        const onUpdate = (state) => {
            // state: { lat, lon, heading, segmentIndex, t, speedLimitKmh }
            const detections = detectOffenders(
                map,
                state,
                dummyCars,
                latlngs,
                speedsPerSegment,
            );

            if (detections.length) {
                processDetections(
                    detections,
                    updateFinesLegend,
                    appendRadarLogEntry,
                );
            }
        };

        // Run police car animation (with FOV + legend)
        // animateCar now returns a cancel function
        stopCarAnimation = animateCar(
            carMarker,
            latlngs,
            speedsPerSegment,
            15,
            onUpdate,
        );
    }

    await rebuildRoute();

    // Rebuild route + car + simulation on reordering
    initStopsTable(stops, rebuildRoute);
});
