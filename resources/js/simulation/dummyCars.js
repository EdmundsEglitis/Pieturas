// resources/js/simulation/dummyCars.js
import L from 'leaflet';
import { interpolate } from '../utils.js';

export function createDummyCar(map, latlngs, speedKmh = 80) {
    return {
        marker: L.circleMarker(latlngs[latlngs.length - 1], {
            radius: 6,
            color: 'blue',
            fillOpacity: 0.8
        }).addTo(map),

        speedMS: speedKmh / 3.6,
        index: latlngs.length - 1,
        progress: 0,
    };
}

export function updateDummyCars(map, dummyCars, latlngs) {
    dummyCars.forEach(car => {
        if (car.index <= 0) return;

        const [lat1, lon1] = latlngs[car.index];
        const [lat2, lon2] = latlngs[car.index - 1];

        const dist = map.distance([lat1, lon1], [lat2, lon2]);
        car.progress += car.speedMS / 60;

        if (car.progress >= dist) {
            car.index--;
            car.progress = 0;
            return;
        }

        const t = car.progress / dist;
        const pos = interpolate([lat1, lon1], [lat2, lon2], t);
        car.marker.setLatLng(pos);
    });
}
