// resources/js/simulation/uiLegend.js
export function updateFinesLegend(fines) {
    const el = document.getElementById('car-fined');
    if (!el) return;

    el.textContent = fines.length;
}
