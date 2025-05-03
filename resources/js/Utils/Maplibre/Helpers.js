export function getBoundsFromPoints(points = []) {
    const lngs = points.map((p) => p[0]);
    const lats = points.map((p) => p[1]);
    const sw = [Math.min(...lngs), Math.min(...lats)];
    const ne = [Math.max(...lngs), Math.max(...lats)];

    return [sw, ne];
}
