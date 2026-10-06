export function smtpPayloadFromFormData(fields) {
    return {
        enable: fields.has('enable'),
        server: String(fields.get('server') || '').trim(),
        port: Number(fields.get('port')),
        username: String(fields.get('username') || '').trim(),
        password: String(fields.get('password') || ''),
        useSsl: Number(fields.get('useSsl')),
        sendAddress: String(fields.get('sendAddress') || '').trim(),
        recipients: fields.getAll('recipients')
            .map((value) => String(value).trim())
            .filter(Boolean),
        title: String(fields.get('title') || '').trim(),
    };
}

export function inputTypeForSmtpField(name) {
    return name === 'port' ? 'number' : 'text';
}

const add = (parent, tag, options = {}) => {
    const node = parent.ownerDocument.createElement(tag);
    if (options.className) node.className = options.className;
    if (options.text !== undefined) node.textContent = options.text;
    for (const [name, value] of Object.entries(options.attributes || {})) {
        node.setAttribute(name, value);
    }
    parent.append(node);
    return node;
};

const field = (grid, labelText, name, value, options = {}) => {
    const label = add(grid, 'label', {className: options.full ? 'form-field form-field-full' : 'form-field'});
    add(label, 'span', {className: 'form-label', text: labelText});
    const input = add(label, 'input', {
        className: 'form-input',
        attributes: {
            name,
            type: options.type || 'text',
            ...(options.placeholder ? {placeholder: options.placeholder} : {}),
            ...(options.min ? {min: options.min} : {}),
            ...(options.max ? {max: options.max} : {}),
            ...(options.required ? {required: ''} : {}),
        },
    });
    input.value = value ?? '';
    return input;
};

export function renderSmtpForm(model, handlers, documentRef = document) {
    const root = documentRef.createElement('div');
    root.className = 'smtp-editor';
    const form = add(root, 'form', {className: 'smtp-form'});

    const toggle = add(form, 'label', {className: 'smtp-toggle'});
    const toggleText = add(toggle, 'span');
    add(toggleText, 'strong', {text: 'Email sending'});
    add(toggleText, 'small', {text: model.enable ? 'Enabled on this camera' : 'Disabled on this camera'});
    const enabled = add(toggle, 'input', {attributes: {type: 'checkbox', name: 'enable'}});
    enabled.checked = Boolean(model.enable);

    add(form, 'h3', {text: 'SMTP server'});
    const serverGrid = add(form, 'div', {className: 'form-grid'});
    field(serverGrid, 'Server', 'server', model.server, {required: true});
    field(serverGrid, 'Port', 'port', model.port, {type: inputTypeForSmtpField('port'), min: '1', max: '65535', required: true});
    field(serverGrid, 'Username', 'username', model.username);
    field(serverGrid, 'Password', 'password', model.password, {type: inputTypeForSmtpField('password')});
    const sslLabel = add(serverGrid, 'label', {className: 'form-field form-field-full'});
    add(sslLabel, 'span', {className: 'form-label', text: 'Connection security'});
    const ssl = add(sslLabel, 'select', {className: 'form-input', attributes: {name: 'useSsl'}});
    for (const [value, text] of [['0', 'None'], ['1', 'SSL / TLS']]) {
        const option = add(ssl, 'option', {text, attributes: {value}});
        option.selected = Number(value) === Number(model.useSsl);
    }

    add(form, 'h3', {text: 'Message'});
    const messageGrid = add(form, 'div', {className: 'form-grid'});
    field(messageGrid, 'From address', 'sendAddress', model.sendAddress);
    field(messageGrid, 'Subject', 'title', model.title);
    const recipients = Array.from({length: 5}, (_, index) => {
        const value = model.recipients?.[index];
        return value === 'none' ? '' : (value || '');
    });
    recipients.forEach((value, index) => field(
        messageGrid,
        `Recipient ${index + 1}`,
        'recipients',
        value,
        {full: index === 0}
    ));

    const note = add(form, 'p', {
        className: 'form-note',
        text: 'Saving reads the latest SMTP object, merges these fields, writes only NetWork.NetEmail, then reads it back and verifies it. An empty password clears the camera password.',
    });
    note.setAttribute('role', 'note');

    const review = add(form, 'div', {className: 'smtp-review', attributes: {hidden: ''}});
    add(review, 'h3', {text: 'Review changes'});
    const reviewList = add(review, 'ul');
    const reviewActions = add(review, 'div', {className: 'form-actions'});
    const cancel = add(reviewActions, 'button', {text: 'Continue editing', attributes: {type: 'button'}});
    const save = add(reviewActions, 'button', {
        className: 'primary-button',
        text: 'Save changes',
        attributes: {type: 'button'},
    });

    const actions = add(form, 'div', {className: 'form-actions'});
    const reload = add(actions, 'button', {text: 'Reload', attributes: {type: 'button'}});
    add(actions, 'button', {
        className: 'primary-button',
        text: 'Review changes',
        attributes: {type: 'submit'},
    });

    let pending = null;
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        pending = smtpPayloadFromFormData(new FormData(form));
        reviewList.replaceChildren();
        const rows = [
            ['Email sending', pending.enable ? 'Enabled' : 'Disabled'],
            ['Server', `${pending.server}:${pending.port}`],
            ['Username', pending.username || '(none)'],
            ['Password', pending.password || '(empty)'],
            ['Security', pending.useSsl === 1 ? 'SSL / TLS' : 'None'],
            ['From', pending.sendAddress || '(empty)'],
            ['Recipients', pending.recipients.join(', ') || '(none)'],
            ['Subject', pending.title || '(empty)'],
        ];
        for (const [name, value] of rows) add(reviewList, 'li', {text: `${name}: ${value}`});
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
