// Utility functions
export function canonicalizeSegment(seg) {
    const lat1 = Number(seg.lat1.toFixed(7));
    const lon1 = Number(seg.lon1.toFixed(7));
    const lat2 = Number(seg.lat2.toFixed(7));
    const lon2 = Number(seg.lon2.toFixed(7));
    if (lat1 > lat2 || (lat1 === lat2 && lon1 > lon2)) {
        return { lat1: lat2, lon1: lon2, lat2: lat1, lon2: lon1 };
    }
    return { lat1, lon1, lat2, lon2 };
}

export function formatDistance(m) {
    return m < 1000 ? `${m.toFixed(0)} m` : `${(m / 1000).toFixed(2)} km`;
}

export function formatTime(s) {
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    return h > 0 ? `${h} h ${m} min` : `${m} min`;
}

export function toggleDirections() {
    const box = document.getElementById('directions-box');
    if (box) box.classList.toggle('hidden');
}

export function calculateHeading(lat1, lon1, lat2, lon2) {
    const toRad = d => (d * Math.PI) / 180;
    const toDeg = r => (r * 180) / Math.PI;

    const φ1 = toRad(lat1);
    const φ2 = toRad(lat2);
    const Δλ = toRad(lon2 - lon1);

    const y = Math.sin(Δλ) * Math.cos(φ2);
    const x =
        Math.cos(φ1) * Math.sin(φ2) -
        Math.sin(φ1) * Math.cos(φ2) * Math.cos(Δλ);

    return (toDeg(Math.atan2(y, x)) + 360) % 360;
}


export function interpolate([lat1, lon1], [lat2, lon2], t) {
    const lat = lat1 + (lat2 - lat1) * t;
    const lon = lon1 + (lon2 - lon1) * t;
    return [Number(lat.toFixed(7)), Number(lon.toFixed(7))];
}

