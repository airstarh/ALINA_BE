export const RULE_EXTENT = 8192;
const clamp = value => Math.max(0, Math.min(RULE_EXTENT, Math.round(value)));

export function normalizedPoint(event, bounds) {
    return {
        X: clamp((event.clientX - bounds.left) * RULE_EXTENT / bounds.width),
        Y: clamp((event.clientY - bounds.top) * RULE_EXTENT / bounds.height),
    };
}

export function moveRegionEdge(points, edge, dx, dy) {
    return points.map((point, index) => index === edge || index === (edge + 1) % 4
        ? {X: clamp(point.X + dx), Y: clamp(point.Y + dy)}
        : {...point});
}

export function validRegion(points) {
    if (!Array.isArray(points) || points.length !== 4) return false;
    const signs = points.map((point, index) => {
        const next = points[(index + 1) % 4];
        const after = points[(index + 2) % 4];
        return (next.X - point.X) * (after.Y - next.Y) - (next.Y - point.Y) * (after.X - next.X);
    });
    return signs.every(value => value > 0) || signs.every(value => value < 0);
}
