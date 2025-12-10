// resources/js/simulation/uiLegend.js

/**
 * Update the "Fined cars" counter in the legend
 * Expects an element with id="car-fines"
 */
export function updateFinesLegend(count) {
    const el = document.getElementById('car-fines');
    if (!el) return;
    el.textContent = String(count);
}

/**
 * Append one line to the radar log box.
 * Expects a container with id="radar-log-entries"
 */
export function appendRadarLogEntry(text) {
    const container = document.getElementById('radar-log-entries');
    if (!container) return;

    const line = document.createElement('div');
    line.textContent = text;

    // Newest entry on top
    container.prepend(line);

    // Prevent unbounded growth
    const MAX_ENTRIES = 100;
    while (container.children.length > MAX_ENTRIES) {
        container.removeChild(container.lastChild);
    }
}

/**
 * Clear radar log + reset fines text (used on route rebuild)
 */
export function clearRadarLog() {
    const container = document.getElementById('radar-log-entries');
    if (container) {
        container.innerHTML = '';
    }

    const finesEl = document.getElementById('car-fines');
    if (finesEl) {
        finesEl.textContent = '0';
    }
}
