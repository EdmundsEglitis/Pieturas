// Functions related to stops table and ordering
import Sortable from 'sortablejs';

export function initStopsTable(stops, drawRoute) {
    const tbody = document.getElementById('stops-table-body');
    if (!tbody) return;

    Sortable.create(tbody, {
        animation: 150,
        onEnd: () => {
            const orderedStops = Array.from(tbody.querySelectorAll('tr')).map(tr => {
                const id = parseInt(tr.dataset.id);
                return stops.find(s => s.id === id);
            });
            drawRoute(orderedStops);
        }
    });
}
