const clone = value => JSON.parse(JSON.stringify(value));
const same = (a, b) => JSON.stringify(a) === JSON.stringify(b);
const permissions = value => [...value].sort();
const fields = (row, kind) => kind === 'users'
    ? {name: row.name, memo: row.memo, group: row.group, multilogin: row.multilogin, permissions: permissions(row.permissions)}
    : {name: row.name, memo: row.memo, permissions: permissions(row.permissions)};

export function createAccessDraft(model) {
    const rows = (items, kind) => (items ?? []).map(item => ({...clone(item),
        key: `${kind}:${item.name}`, originalName: item.name, delete: false,
        ...(kind === 'users' ? {password: null, setEmpty: false} : {}), baseline: fields(item, kind)}));
    return {revision: model.revision, users: rows(model.users, 'users'), groups: rows(model.groupRows, 'groups'),
        services: (model.points ?? []).map(point => ({...clone(point), baselinePort: point.port}))};
}

export function accessChanges(draft) {
    const result = {revision: draft.revision, users: [], groups: [], services: []};
    for (const kind of ['users', 'groups']) for (const row of draft[kind]) {
        if (row.originalName === null && row.delete) continue;
        if (row.originalName !== null && !row.delete && same(fields(row, kind), row.baseline)
            && (kind !== 'users' || (!row.setEmpty && row.password === null))) continue;
        result[kind].push({key: row.key, originalName: row.originalName, delete: row.delete, ...fields(row, kind),
            ...(kind === 'users' ? {password: row.setEmpty ? null : row.password, setEmpty: row.setEmpty} : {})});
    }
    for (const row of draft.services) {
        if (!row.portEditable) continue;
        const value = String(row.port);
        if (!/^\d+$/.test(value) || Number(value) < 1 || Number(value) > 65535) throw new Error(`${row.name}: port must be 1–65535.`);
        if (Number(value) !== row.baselinePort) result.services.push({id: row.id, port: Number(value)});
    }
    return result;
}

export function rebaseAccessDraft(model, previous, applied) {
    const next = createAccessDraft(model);
    const saved = new Set(applied.map(row => `${row.kind}:${row.key}`));
    const changes = accessChanges(previous);
    for (const kind of ['users', 'groups']) {
        const singular = kind === 'users' ? 'user' : 'group';
        for (const changed of changes[kind]) {
            if (saved.has(`${singular}:${changed.key}`)) continue;
            const old = previous[kind].find(row => row.key === changed.key);
            const existing = next[kind].findIndex(row => row.name === old.originalName || row.name === old.name);
            const baseline = existing >= 0 ? next[kind][existing] : null;
            const retained = {...clone(old), originalName: baseline?.originalName ?? old.originalName,
                baseline: baseline?.baseline ?? old.baseline};
            if (existing >= 0) next[kind][existing] = retained;
            else next[kind].push(retained);
        }
    }
    for (const changed of changes.services) {
        if (saved.has(`service:${changed.id}`)) continue;
        const row = next.services.find(item => item.id === changed.id);
        if (row) row.port = changed.port;
    }
    return next;
}

export function renderAccessForm(model, document = globalThis.document, actions = {}) {
    const root = document.createElement('form');
    root.className = 'access-overview access-manager';
    let draft = createAccessDraft(model), serial = 0, controls = [], sections = {}, save, status;
    const userSelections = new Map();
    const node = (tag, text = '', className = '') => {
        const element = document.createElement(tag);
        element.className = className;
        if (text !== '') element.textContent = text;
        return element;
    };
    const button = (caption, action, className = 'quiet-button') => {
        const element = node('button', caption, className);
        element.type = 'button';
        element.addEventListener('click', action);
        controls.push(element);
        return element;
    };
    const input = (value, caption, update, type = 'text') => {
        const element = node('input', '', 'form-input');
        element.type = type;
        if (type === 'checkbox') element.checked = value; else element.value = value;
        element.setAttribute('aria-label', caption);
        element.setAttribute('autocomplete', 'off');
        element.addEventListener(type === 'checkbox' ? 'change' : 'input', () => {
            update(type === 'checkbox' ? element.checked : element.value); refresh();
        });
        controls.push(element);
        return element;
    };
    const select = (options, selected, caption, multiple, update) => {
        const element = node('select', '', 'form-input');
        element.multiple = multiple;
        if (multiple) element.size = 5;
        element.setAttribute('aria-label', caption);
        for (const value of options) {
            const option = node('option', value);
            option.value = value;
            option.selected = multiple ? selected.includes(value) : selected === value;
            element.append(option);
        }
        if (!multiple) element.value = selected;
        element.addEventListener('change', () => {
            update(multiple ? Array.from(element.options ?? element.children).filter(option => option.selected).map(option => option.value) : element.value);
            refresh();
        });
        controls.push(element);
        return element;
    };
    const refresh = () => {
        if (!save) return;
        try {
            const changes = accessChanges(draft);
            const count = changes.users.length + changes.groups.length + changes.services.length;
            save.disabled = count === 0 || !model.revision;
            status.textContent = count ? `${count} changed row${count === 1 ? '' : 's'} pending.` : 'No pending changes.';
        } catch (error) { save.disabled = true; status.textContent = error.message; }
    };
    const cell = (row, ...content) => { const element = node('td'); element.append(...content); row.append(element); return element; };
    const table = headers => {
        const wrapper = node('div', '', 'access-table-scroll');
        const element = node('table', '', 'access-table');
        const head = node('thead'), headings = node('tr'), body = node('tbody');
        for (const caption of headers) headings.append(node('th', caption));
        head.append(headings); element.append(head, body); wrapper.append(element);
        return {wrapper, body};
    };
    const section = (id, title, open) => {
        const element = node('details', '', 'access-section');
        element.dataset.sectionId = id;
        element.open = open;
        element.append(node('summary', title));
        element.addEventListener('toggle', () => { sections[id] = element.open; });
        root.append(element);
        return element;
    };
    const groupOptions = () => draft.groups.filter(row => !row.delete).map(row => row.name);
    const updateGroup = (oldName, newName) => {
        for (const row of draft.users) if (row.group === oldName) row.group = newName;
        draw();
    };
    const markDelete = (row, kind) => {
        if (row.originalName === null) draft[kind] = draft[kind].filter(item => item !== row);
        else row.delete = !row.delete;
        draw();
    };
    const pendingStyle = row => row.delete ? 'access-deleted' : '';
    const updateUserSelection = row => {
        const selection = userSelections.get(row.key);
        if (!selection) return;
        const allowed = draft.groups.find(group => group.name === row.group)?.permissions ?? [];
        for (const option of selection.children) {
            option.selected = row.permissions.includes(option.value);
            option.disabled = !allowed.includes(option.value)
                || (row.active && ['Account', 'NetConfig'].includes(option.value));
        }
    };

    const draw = () => {
        for (const element of root.children) if (element.className === 'access-section') {
            sections[element.dataset.sectionId] = element.open;
        }
        root.replaceChildren(); controls = []; userSelections.clear();
        root.append(node('p', model.note, 'form-note'));
        if (model.managementError) root.append(node('p', model.managementError, 'warning-note'));
        if (model.retainedPermissions?.length) root.append(node('p', `Permissions present in existing camera groups are retained: ${model.retainedPermissions.join(', ')}.`, 'form-help'));
        const usersSection = section('users', 'USERS', sections.users ?? true);
        usersSection.append(node('p', 'Created via is recorded only for accounts created by this app. Unknown is not a protocol restriction.', 'form-help'));
        const memoUsers = (model.users ?? []).some(row => row.memoEditable);
        const users = table(['Created via', 'Name', 'Password', 'Multilogin', 'Group', ...(memoUsers ? ['Memo'] : []), 'Permissions', 'Delete']);
        for (const row of draft.users) {
            const tr = node('tr', '', pendingStyle(row));
            cell(tr, node('span', row.createdVia ?? 'DVRIP (on Save)'));
            if (row.reserved || row.active) cell(tr, node('span', row.name), node('small', row.reserved ? 'Reserved' : 'Connected account', 'form-help'));
            else {
                const name = input(row.name, 'User name', value => { row.name = value; });
                name.required = true; name.pattern = '[a-zA-Z0-9_.-]{1,32}'; name.maxLength = 32; name.disabled = row.delete;
                cell(tr, name);
            }
            const password = input(row.password ?? '', `New password for ${row.name}`, value => { row.password = value === '' ? null : value; });
            password.placeholder = 'set new password'; password.maxLength = 64;
            password.disabled = row.setEmpty || row.delete;
            password.required = row.originalName === null && !row.setEmpty;
            const emptyLabel = node('label', '', 'access-empty');
            const empty = input(row.setEmpty, `Set empty password for ${row.name}`, value => {
                row.setEmpty = value; password.disabled = value || row.delete; password.required = row.originalName === null && !value;
            }, 'checkbox');
            empty.disabled = row.delete;
            emptyLabel.append(empty, node('span', 'Set empty')); cell(tr, password, emptyLabel);
            const multi = input(row.multilogin, `Multilogin for ${row.name}`, value => { row.multilogin = value; }, 'checkbox');
            multi.disabled = row.delete; cell(tr, multi);
            const group = select(groupOptions(), row.group, `Group for ${row.name}`, false, value => {
                row.group = value;
                const allowed = draft.groups.find(item => item.name === value)?.permissions ?? [];
                row.permissions = row.originalName === null ? [...allowed] : row.permissions.filter(permission => allowed.includes(permission));
                updateUserSelection(row);
            });
            group.disabled = row.delete; cell(tr, group);
            if (memoUsers) {
                if (row.memoEditable !== false) { const memo = input(row.memo, `Memo for ${row.name}`, value => { row.memo = value; }); memo.disabled = row.delete; cell(tr, memo); }
                else cell(tr, node('span', 'Not supported'));
            }
            const permission = select(model.permissions ?? [], row.permissions, `Permissions for ${row.name}`, true, value => { row.permissions = value; });
            userSelections.set(row.key, permission);
            const allowed = draft.groups.find(item => item.name === row.group)?.permissions ?? [];
            for (const option of permission.children) option.disabled = !allowed.includes(option.value)
                || (row.active && ['Account', 'NetConfig'].includes(option.value) && option.selected);
            permission.disabled = row.delete; cell(tr, permission);
            const remove = button(row.delete ? 'Undo' : 'Delete', () => markDelete(row, 'users'));
            remove.disabled = row.reserved || row.active;
            if (remove.disabled) remove.title = 'Reserved or connected accounts cannot be deleted.';
            cell(tr, remove); users.body.append(tr);
        }
        usersSection.append(users.wrapper, button('Add New row', () => {
            const group = draft.groups.find(row => !row.delete && row.name === 'user') ?? draft.groups.find(row => !row.delete);
            draft.users.push({key: `new-user-${++serial}`, originalName: null, name: '', memo: '', memoEditable: memoUsers,
                password: null, setEmpty: false, multilogin: true, group: group?.name ?? '', permissions: [...(group?.permissions ?? [])],
                delete: false, baseline: null, createdVia: 'DVRIP (on Save)'});
            draw();
        }));

        const groupsSection = section('groups', 'GROUPS', sections.groups ?? false);
        const memoGroups = (model.groupRows ?? []).some(row => row.memoEditable);
        const groups = table(['Name', ...(memoGroups ? ['Memo'] : []), 'Permissions', 'Delete']);
        for (const row of draft.groups) {
            const tr = node('tr', '', pendingStyle(row));
            if (row.active) cell(tr, node('span', row.name), node('small', 'Connected account group', 'form-help'));
            else {
                let before = row.name;
                const name = input(row.name, 'Group name', value => { row.name = value; });
                name.required = true; name.pattern = '[a-zA-Z0-9_.-]{1,32}'; name.maxLength = 32; name.disabled = row.delete;
                name.addEventListener('change', () => { const previous = before; before = row.name; updateGroup(previous, row.name); });
                cell(tr, name);
            }
            if (memoGroups) {
                if (row.memoEditable !== false) { const memo = input(row.memo, `Memo for group ${row.name}`, value => { row.memo = value; }); memo.disabled = row.delete; cell(tr, memo); }
                else cell(tr, node('span', 'Not supported'));
            }
            const selection = select(model.permissions ?? [], row.permissions, `Permissions for group ${row.name}`, true, value => {
                row.permissions = value;
                for (const user of draft.users) if (user.group === row.name) {
                    user.permissions = user.permissions.filter(permission => value.includes(permission)); updateUserSelection(user);
                }
            });
            selection.disabled = row.delete; cell(tr, selection);
            const remove = button(row.delete ? 'Undo' : 'Delete', () => markDelete(row, 'groups'));
            remove.disabled = row.active;
            if (row.active) remove.title = 'This group is used by the connected account.';
            cell(tr, remove); groups.body.append(tr);
        }
        groupsSection.append(groups.wrapper, button('Add New row', () => {
            draft.groups.push({key: `new-group-${++serial}`, originalName: null, name: '', memo: '', memoEditable: memoGroups,
                permissions: [], active: false, delete: false, baseline: null}); draw();
        }));

        const servicesSection = section('services', 'SERVICES', sections.services ?? false);
        const services = table(['Service / URL', 'Port']);
        for (const row of draft.services) {
            const tr = node('tr');
            const info = node('div', '', 'access-service-info');
            info.append(node('span', row.name, 'access-service-name'));
            const url = node('div', '', 'access-service-url');
            url.append(node('span', row.url, 'access-url'), button('⧉', async () => {
                try { await globalThis.navigator.clipboard.writeText(row.url); status.textContent = 'URL copied.'; }
                catch { status.textContent = 'Clipboard unavailable. Select and copy the displayed URL.'; }
            }));
            url.children[1].title = 'Copy URL'; url.children[1].setAttribute('aria-label', `Copy ${row.name} URL`);
            info.append(url, node('p', row.note, 'form-help')); cell(tr, info);
            if (row.portEditable) { const port = input(row.port, `${row.name} port`, value => { row.port = value; }); port.inputMode = 'numeric'; port.pattern = '[0-9]+'; port.required = true; cell(tr, port, node('small', row.portReason, 'form-help')); }
            else cell(tr, node('span', String(row.port)), node('small', row.portReason, 'form-help'));
            services.body.append(tr);
        }
        servicesSection.append(services.wrapper);
        const footer = node('div', '', 'access-save');
        save = node('button', 'Save changes', 'primary-button'); save.type = 'submit'; controls.push(save);
        status = node('p', '', 'form-help'); status.setAttribute('role', 'status');
        footer.append(save, status); root.append(footer); refresh();
    };
    root.addEventListener('submit', async event => {
        event.preventDefault();
        if (save.disabled || !actions.onSave) return;
        const payload = accessChanges(draft);
        const before = controls.map(element => [element, element.disabled]);
        for (const [element] of before) element.disabled = true;
        status.textContent = 'Saving changed rows…';
        try {
            const result = await actions.onSave(payload);
            if (result.model) {
                draft = result.complete ? createAccessDraft(result.model) : rebaseAccessDraft(result.model, draft, result.applied);
                model = result.model; draw();
            }
            status.textContent = result.message;
        } catch (error) { status.textContent = error.message || 'Saving camera settings failed.'; }
        finally {
            for (const [element, disabled] of before) element.disabled = disabled;
        }
    });
    draw();
    return root;
}
