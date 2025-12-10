// resources/js/simulation/policeCar.js
import { calculateHeading, interpolate } from '../utils.js';

const MODEL_HEADING_OFFSET = 90;

export function animatePoliceCar(
    carMarker,
    latlngs,
    speedsPerSegment,
    speedMetersPerSecond,
    onUpdate // callback → radar
) {
    let index = 0;
    let progress = 0;
    let currentHeading = null;

    function step() {
        const map = carMarker._map;
        if (!map || index >= latlngs.length - 1) return;

        const [lat1, lon1] = latlngs[index];
        const [lat2, lon2] = latlngs[index + 1];

        const dist = map.distance([lat1, lon1], [lat2, lon2]);
        if (!dist) return;

        progress += speedMetersPerSecond / 60;

        if (progress >= dist) {
            index++;
            progress = 0;
            requestAnimationFrame(step);
            return;
        }

        const t = progress / dist;
        const [lat, lon] = interpolate([lat1, lon1], [lat2, lon2], t);
        carMarker.setLatLng([lat, lon]);

        const heading = calculateHeading(lat1, lon1, lat2, lon2);
        if (currentHeading == null) currentHeading = heading;
        currentHeading += (heading - currentHeading) * 0.15;

        const el = carMarker.getElement();
        if (el) {
            const translate =
                el.style.transform.match(/translate3d\([^)]+\)/)?.[0] || '';
            el.style.transform =
                `${translate} rotate(${currentHeading + MODEL_HEADING_OFFSET}deg)`;
        }

        // 👉 send telemetry to radar
        onUpdate({
            position: [lat, lon],
            heading: currentHeading,
            segmentIndex: index,
            t
        });

        requestAnimationFrame(step);
    }

    step();
}
