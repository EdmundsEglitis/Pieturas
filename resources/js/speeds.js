// Speed-color mapping
export const speedColors = {
    20:  '#e6194B',
    30:  '#f58231',
    40:  '#ffe119',
    50:  '#bfef45',
    60:  '#3cb44b',
    70:  '#42d4f4',
    80:  '#4363d8',
    90:  '#911eb4',
    100: '#f032e6',
    110: '#a9a9a9',
    120: '#000000',
};

export function getColorForSpeed(speed) {
    return speedColors[speed] ?? '#999';
}
