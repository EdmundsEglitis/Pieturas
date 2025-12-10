/**
 * Append one radar / enforcement log entry
 */
export function logRadarEvent({
    timestamp,
    distance,
    offenderLat,
    offenderLon,
    offenderSpeed,
    speedLimitAtOffender,
    policeSpeedLimit,
    result
}) {
    const log = document.getElementById('radar-log');
    if (!log) return;

    const div = document.createElement('div');
    div.className = `radar-log-entry ${result}`;

    div.innerHTML = `
        <div><strong>${timestamp}</strong></div>
        <div>Distance: ${distance.toFixed(1)} m</div>
        <div>Offender pos: ${offenderLat.toFixed(6)}, ${offenderLon.toFixed(6)}</div>
        <div>Offender speed: ${offenderSpeed.toFixed(1)} km/h</div>
        <div>Speed limit @ offender: ${speedLimitAtOffender ?? 'UNKNOWN'} km/h</div>
        <div>Police speed limit: ${policeSpeedLimit ?? 'UNKNOWN'} km/h</div>
        <div><strong>Result: ${result.toUpperCase()}</strong></div>
    `;

    log.appendChild(div);

    // Auto-scroll to bottom
    log.scrollTop = log.scrollHeight;
}
