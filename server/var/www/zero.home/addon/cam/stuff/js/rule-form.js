import {normalizedPoint, moveRegionEdge, validRegion, RULE_EXTENT} from './rule-geometry.js';

const svgNS = 'http://www.w3.org/2000/svg';
const clone = value => JSON.parse(JSON.stringify(value));
const add = (parent, tag, text = '') => {
    const node = parent.ownerDocument.createElement(tag);
    if (text) node.textContent = text;
    parent.append(node);
    return node;
};
const shape = (svg, tag, attributes) => {
    const node = svg.ownerDocument.createElementNS(svgNS, tag);
    for (const [key, value] of Object.entries(attributes)) node.setAttribute(key, String(value));
    svg.append(node);
    return node;
};

export function renderRuleSection(form, model, cameraId) {
    const original = clone(model.ruleGeometry);
    let line = clone(original.line);
    let region = clone(original.region);
    const details = add(form, 'details');
    details.className = 'rule-section';
    add(details, 'summary', 'Detection rule / area');
    const note = add(details, 'p', 'Tripwire detects crossing of its line; Intrusion detects entry into its four-sided area. The inactive shape is preserved. Drag the green points or blue sides.');
    note.className = 'form-note';
    const controls = add(details, 'div');
    controls.className = 'rule-controls';
    const enabledLabel = add(controls, 'label');
    const enabled = add(enabledLabel, 'input');
    enabled.type = 'checkbox'; enabled.name = 'ruleEnabled'; enabled.checked = Boolean(model.values.ruleEnabled);
    enabledLabel.append(' Enable detection rule');
    const modeLabel = add(controls, 'label', 'Rule mode ');
    const mode = add(modeLabel, 'select');
    mode.name = 'ruleType';
    for (const [value, label] of [['0', 'Tripwire'], ['1', 'Intrusion area']]) {
        const option = add(mode, 'option', label);
        option.value = value;
    }
    mode.value = String(model.values.ruleType);
    const toolbar = add(details, 'div');
    toolbar.className = 'rule-toolbar';
    const refresh = add(toolbar, 'button', 'Refresh image'); refresh.type = 'button';
    const discard = add(toolbar, 'button', 'Discard shape edits'); discard.type = 'button';
    const status = add(details, 'p', 'Image is a reference; coordinates are stored on the camera’s 0–8192 grid.');
    status.className = 'form-note';
    const surface = add(details, 'div'); surface.className = 'rule-surface';
    const image = add(surface, 'img'); image.alt = 'Current camera still image for placing detection rule';
    image.draggable = false;
    const svg = surface.ownerDocument.createElementNS(svgNS, 'svg');
    svg.setAttribute('viewBox', `0 0 ${RULE_EXTENT} ${RULE_EXTENT}`);
    svg.setAttribute('preserveAspectRatio', 'none');
    svg.setAttribute('aria-label', 'Detection rule geometry');
    surface.append(svg);
    const refreshImage = () => {
        image.src = `stuff/php/snapshot.php?camera=${encodeURIComponent(cameraId)}&t=${Date.now()}`;
    };
    image.addEventListener('error', () => { status.textContent = 'Still image unavailable. Reload later; shape editing is paused.'; svg.style.pointerEvents = 'none'; });
    image.addEventListener('load', () => { status.textContent = 'Drag green points or blue sides. Shape edits stay local until Save changes.'; svg.style.pointerEvents = ''; });
    refresh.addEventListener('click', refreshImage);
    refreshImage();

    const render = () => {
        svg.replaceChildren();
        const isLine = mode.value === '0';
        if (isLine) {
            const points = [{X: line.StartX, Y: line.StartY}, {X: line.StopX, Y: line.StopY}];
            shape(svg, 'line', {x1: points[0].X, y1: points[0].Y, x2: points[1].X, y2: points[1].Y, class: 'rule-stroke'});
            points.forEach((point, index) => {
                const handle = shape(svg, 'circle', {cx: point.X, cy: point.Y, r: 115, class: 'rule-handle'});
                handle.addEventListener('pointerdown', event => startDrag(event, 'line', index));
            });
        } else {
            shape(svg, 'polygon', {points: region.map(point => `${point.X},${point.Y}`).join(' '), class: 'rule-polygon'});
            region.forEach((point, index) => {
                const next = region[(index + 1) % 4];
                shape(svg, 'line', {x1: point.X, y1: point.Y, x2: next.X, y2: next.Y, class: 'rule-edge-visible'});
                const edge = shape(svg, 'line', {x1: point.X, y1: point.Y, x2: next.X, y2: next.Y, class: 'rule-edge-hit'});
                edge.addEventListener('pointerdown', event => startDrag(event, 'edge', index));
            });
            region.forEach((point, index) => {
                const handle = shape(svg, 'circle', {cx: point.X, cy: point.Y, r: 115, class: 'rule-handle'});
                handle.addEventListener('pointerdown', event => startDrag(event, 'corner', index));
            });
        }
    };
    let drag = null;
    const startDrag = (event, kind, index) => {
        event.preventDefault();
        drag = {kind, index, origin: normalizedPoint(event, svg.getBoundingClientRect()), line: clone(line), region: clone(region)};
        svg.setPointerCapture(event.pointerId);
    };
    svg.addEventListener('pointermove', event => {
        if (!drag) return;
        const point = normalizedPoint(event, svg.getBoundingClientRect());
        if (drag.kind === 'line') {
            line = clone(drag.line);
            const prefix = drag.index === 0 ? 'Start' : 'Stop';
            line[`${prefix}X`] = point.X; line[`${prefix}Y`] = point.Y;
        } else if (drag.kind === 'corner') {
            region = clone(drag.region); region[drag.index] = point;
        } else {
            region = moveRegionEdge(drag.region, drag.index, point.X - drag.origin.X, point.Y - drag.origin.Y);
        }
        render();
    });
    svg.addEventListener('pointerup', () => { drag = null; });
    svg.addEventListener('pointercancel', () => { drag = null; });
    mode.addEventListener('change', render);
    discard.addEventListener('click', () => { line = clone(original.line); region = clone(original.region); render(); });
    render();
    return {
        collectChanges() {
            const changes = {};
            if (JSON.stringify(line) !== JSON.stringify(original.line)) {
                if (line.StartX === line.StopX && line.StartY === line.StopY) { details.open = true; status.textContent = 'Tripwire endpoints must differ.'; return null; }
                changes.line = {expected: original.line, points: line};
            }
            if (JSON.stringify(region) !== JSON.stringify(original.region)) {
                if (!validRegion(region)) { details.open = true; status.textContent = 'Intrusion area must be a non-crossing four-sided shape.'; return null; }
                changes.region = {expected: original.region, points: region};
            }
            return changes;
        },
        describe(changes) { return Object.keys(changes).map(kind => `${kind === 'line' ? 'Tripwire' : 'Intrusion area'}: modified`).join(', '); },
    };
}
