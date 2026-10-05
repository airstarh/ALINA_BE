const scalarType = (value) => {
    if (value === null) return 'null';
    if (typeof value === 'boolean') return 'boolean';
    if (typeof value === 'number') return 'number';
    return 'string';
};

const scalarText = (value) => {
    if (value === null) return 'null';
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    return String(value);
};

function renderValue(value, document, label = null) {
    if (value !== null && typeof value === 'object') {
        const entries = Object.entries(value);
        if (entries.length === 0) {
            const empty = document.createElement('span');
            empty.className = 'tree-scalar tree-empty';
            empty.textContent = Array.isArray(value) ? '[]' : '{}';
            return empty;
        }

        const details = document.createElement('details');
        details.className = 'tree-branch';
        details.open = label === null;
        const summary = document.createElement('summary');
        summary.textContent = label ?? (Array.isArray(value) ? 'List' : 'Object');
        details.append(summary, renderTree(value, document));
        return details;
    }

    const scalar = document.createElement('span');
    scalar.className = `tree-scalar tree-${scalarType(value)}`;
    scalar.textContent = scalarText(value);
    return scalar;
}

export function renderTree(value, document = globalThis.document) {
    if (value === null || typeof value !== 'object') {
        return renderValue(value, document);
    }

    const list = document.createElement('dl');
    list.className = 'tree-list';
    for (const [key, child] of Object.entries(value)) {
        const term = document.createElement('dt');
        term.textContent = Array.isArray(value) ? `[${key}]` : key;
        const definition = document.createElement('dd');
        definition.append(renderValue(child, document, String(key)));
        list.append(term, definition);
    }
    return list;
}
