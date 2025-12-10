// resources/js/simulation/enforcement.js
const finedCars = new Set();
const fines = [];

export function processDetections(detections) {
    detections.forEach(({ car, speedLimit }) => {
        if (!speedLimit) return;

        if (car.speedMS * 3.6 > speedLimit && !finedCars.has(car)) {
            finedCars.add(car);
            fines.push({
                speed: car.speedMS * 3.6,
                limit: speedLimit,
                time: Date.now()
            });
        }
    });

    return fines;
}
