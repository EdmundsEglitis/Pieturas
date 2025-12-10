export function updateCarInfo({ speed, heading, distanceToNextSpeed }) {
    const speedEl = document.getElementById('car-speed');
    const headingEl = document.getElementById('car-heading');
    const nextEl = document.getElementById('car-next-speed');

    if (speedEl) speedEl.textContent = speed.toFixed(1);
    if (headingEl) headingEl.textContent = Math.round(heading);
    if (nextEl) {
        nextEl.textContent =
            distanceToNextSpeed != null
                ? `${Math.round(distanceToNextSpeed)} m`
                : '–';
    }
}
