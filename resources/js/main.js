import { initMap } from './map.js';
import { drawRoute } from './route.js';
import { initStopsTable } from './stops.js';
import { placeCar, animateCar } from './car.js';
import { toggleDirections } from './utils.js';

window.toggleDirections = toggleDirections;

document.addEventListener('DOMContentLoaded', async () => {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const tbody = document.getElementById('stops-table-body');
    const stops = tbody ? JSON.parse(tbody.dataset.stops) : [];

    if (!stops.length) return;

    const map = initMap(stops);

    // === INITIAL ROUTE + CAR ===
    const { latlngs, speedsPerSegment } =
        await drawRoute(map, stops, csrfToken);

    if (latlngs.length > 0) {
        const car = placeCar(map, latlngs[0][0], latlngs[0][1]);
        animateCar(car, latlngs, speedsPerSegment, 15);
    }

    // === DRAG REORDER ===
    const redraw = async (orderedStops) => {
        const result = await drawRoute(map, orderedStops, csrfToken);
        return result;
    };

    initStopsTable(stops, redraw);
});
