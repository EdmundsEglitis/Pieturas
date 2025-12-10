// resources/js/simulation/enforcement.js

let totalFines = 0;
const finedIds = new Set();

/**
 * Reset fines & offender tracking (called on route rebuild)
 */
export function resetEnforcement() {
    totalFines = 0;
    finedIds.clear();
}

/**
 * Process radar detections and update fines counter + log
 *
 * @param {Array<Object>} detections
 * @param {function(number):void} updateFinesLegend
 * @param {function(string):void} logFn
 */
export function processDetections(
    detections,
    updateFinesLegend,
    logFn
) {
    if (!detections || !detections.length) return;

    let changed = false;

    detections.forEach(det => {
        const id = det.car.id;
        if (finedIds.has(id)) return; // already fined → ignore

        finedIds.add(id);
        totalFines++;
        changed = true;

        // Build log entry only when we actually fine
        if (typeof logFn === 'function') {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const timestamp = `${hh}:${mm}:${ss}`;

            const { distance, position, speedKmh, limitKmh, overBy } = det;

            const msg = `[${timestamp}] Car #${id} | dist=${distance.toFixed(
                1
            )} m | pos=(${position.lat.toFixed(
                5
            )}, ${position.lng.toFixed(
                5
            )}) | speed=${speedKmh.toFixed(
                1
            )} km/h | limit=${limitKmh} km/h | over=${overBy.toFixed(
                1
            )} km/h`;

            logFn(msg);
        }
    });

    if (changed && typeof updateFinesLegend === 'function') {
        updateFinesLegend(totalFines);
    }
}
