export function servicePayloadFromFormData(model, formData) {
    const values = {};
    for (const [name, field] of Object.entries(model.fields)) {
        if (field.type === 'boolean') values[name] = formData.has(name);
        else if (field.type === 'integer') values[name] = Number(formData.get(name));
        else if (field.type === 'password') values[name] = String(formData.get(name) || '');
        else values[name] = String(formData.get(name) || '').trim();
    }
    return values;
}

const append = (parent, tag, options = {}) => {
    const node = parent.ownerDocument.createElement(tag);
    if (options.className) node.className = options.className;
    if (options.text !== undefined) node.textContent = options.text;
    for (const [name, value] of Object.entries(options.attributes || {})) {
        node.setAttribute(name, value);
    }
    parent.append(node);
    return node;
};

export function renderServiceForm(model, handlers, documentRef = document) {
    const root = documentRef.createElement('div');
    root.className = 'service-editor';
    const form = append(root, 'form', {className: 'service-form'});
    const entries = Object.entries(model.fields);
    const enableEntry = entries.find(([, field]) => field.type === 'boolean');

    if (model.warning) {
        const warning = append(form, 'p', {className: 'form-warning', text: model.warning});
        warning.setAttribute('role', 'note');
    }

    if (enableEntry) {
        const [name, field] = enableEntry;
        const toggle = append(form, 'label', {className: 'smtp-toggle'});
        const copy = append(toggle, 'span');
        append(copy, 'strong', {text: field.label});
        append(copy, 'small', {text: model.values[name] ? 'Enabled on this camera' : 'Disabled on this camera'});
        const input = append(toggle, 'input', {attributes: {type: 'checkbox', name}});
        input.checked = Boolean(model.values[name]);
    }

    append(form, 'h3', {text: 'Settings'});
    const grid = append(form, 'div', {className: 'form-grid'});
    for (const [name, field] of entries) {
        if (field.type === 'boolean') continue;
        const label = append(grid, 'label', {className: 'form-field'});
        append(label, 'span', {className: 'form-label', text: field.label});
        const attributes = {
            name,
            type: field.type === 'integer' ? 'number' : (field.type === 'password' ? 'password' : 'text'),
        };
        if (field.min !== undefined) attributes.min = String(field.min);
        if (field.max !== undefined) attributes.max = String(field.max);
        if (field.type === 'password') {
            attributes.placeholder = field.configured
                ? 'Leave blank to keep current password'
                : 'No password configured';
        }
        const input = append(label, 'input', {className: 'form-input', attributes});
        input.value = model.values[name] ?? '';
    }

    const note = append(form, 'p', {
        className: 'form-note',
        text: `Saving reads the latest ${model.branch} object, merges only these fields, writes that complete branch, then reads it back and verifies it.`,
    });
    note.setAttribute('role', 'note');

    const review = append(form, 'div', {className: 'smtp-review', attributes: {hidden: ''}});
    append(review, 'h3', {text: 'Review changes'});
    const list = append(review, 'ul');
    const reviewActions = append(review, 'div', {className: 'form-actions'});
    const cancel = append(reviewActions, 'button', {text: 'Continue editing', attributes: {type: 'button'}});
    const save = append(reviewActions, 'button', {className: 'primary-button', text: 'Save changes', attributes: {type: 'button'}});

    const actions = append(form, 'div', {className: 'form-actions'});
    const reload = append(actions, 'button', {text: 'Reload', attributes: {type: 'button'}});
    append(actions, 'button', {className: 'primary-button', text: 'Review changes', attributes: {type: 'submit'}});

    let pending = null;
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        pending = servicePayloadFromFormData(model, new FormData(form));
        list.replaceChildren();
        for (const [name, field] of entries) {
            let display = pending[name];
            if (field.type === 'boolean') display = display ? 'Enabled' : 'Disabled';
            if (field.type === 'password') display = display ? 'Replace current password' : 'Keep current password';
            if (display === '') display = '(empty)';
            append(list, 'li', {text: `${field.label}: ${display}`});
        }
        review.hidden = false;
        review.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    });
    cancel.addEventListener('click', () => { review.hidden = true; });
    reload.addEventListener('click', handlers.onReload);
    save.addEventListener('click', async () => {
        if (!pending) return;
        save.disabled = true;
        try {
            await handlers.onSave(pending);
        } finally {
            save.disabled = false;
        }
    });
    return root;
}
