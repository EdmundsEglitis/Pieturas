// Initialize Leaflet map
export function initMap(stops) {
    const mapStops = stops.map(s => L.latLng(s.latitude, s.longitude));
    const map = L.map('map').fitBounds(mapStops);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    return map;
}

