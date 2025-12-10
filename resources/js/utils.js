export function formatDistance(m) {
    return m < 1000 ? `${m.toFixed(0)} m` : `${(m/1000).toFixed(2)} km`;
}

export function formatTime(s) {
    const h = Math.floor(s/3600), m = Math.floor((s%3600)/60);
    return h > 0 ? `${h} h ${m} min` : `${m} min`;
}

export const speedColors = {
    20:  '#e6194B', 30: '#f58231', 40: '#ffe119', 50: '#bfef45',
    60:  '#3cb44b', 70: '#42d4f4', 80: '#4363d8', 90: '#911eb4',
    100: '#f032e6', 110: '#a9a9a9', 120: '#000000'
};

export function getColorForSpeed(speed) {
    return speedColors[speed] ?? '#999';
}

export function canonicalKey(lat1, lon1, lat2, lon2) {
    const a1 = Number(lat1).toFixed(7), o1 = Number(lon1).toFixed(7);
    const a2 = Number(lat2).toFixed(7), o2 = Number(lon2).toFixed(7);
    return (a1 > a2 || (a1 === a2 && o1 > o2))
        ? `${a2}|${o2}|${a1}|${o1}`
        : `${a1}|${o1}|${a2}|${o2}`;
}
