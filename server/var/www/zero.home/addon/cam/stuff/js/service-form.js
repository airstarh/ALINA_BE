import {renderPeriodSection} from './period-form.js?v=20261006-1';
import {renderRuleSection} from './rule-form.js?v=20261007-1';

export function servicePayloadFromFormData(model, formData) {
    const values = {};
    for (const [name, field] of Object.entries(model.fields)) {
        if (!formData.has(name) && isServiceFieldDisabled(field, {...model.values, ...values})) {
            values[name] = model.values[name];
        }
        else if (field.type === 'boolean') values[name] = formData.has(name);
        else if (field.type === 'boolean-integer') values[name] = formData.has(name) ? 1 : 0;
        else if (field.type === 'integer') values[name] = Number(formData.get(name));
        else if (field.type === 'password') values[name] = String(formData.get(name) || '');
        else values[name] = String(formData.get(name) || '').trim();
    }
    return values;
}

export function isServiceFieldDisabled(field, values) {
    return field.disabled === true || (field.disableWhen && Boolean(values[field.disableWhen]));
}

export function serviceOptionLabel(field, value) {
    return field.optionLabels?.[value] || String(value);
}

export function wifiValuesFromAccessPoint(current, accessPoint) {
    return {
        ...current,
        ssid: accessPoint.ssid,
        auth: accessPoint.authentication,
        encryption: accessPoint.encryption,
        channel: accessPoint.channel,
        networkType: accessPoint.networkType,
    };
}

export function inputTypeForServiceField(field) {
    return field.type === 'integer' ? 'number' : 'text';
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
    const isSwitch = (field) => field.type === 'boolean' || field.type === 'boolean-integer';
    const enableEntry = entries.find(([name, field]) => name === 'enable' && isSwitch(field))
        || entries.find(([, field]) => isSwitch(field));

    if (model.warning) {
        const warning = append(form, 'p', {className: 'form-warning', text: model.warning});
        warning.setAttribute('role', 'note');
    }


    if (model.id === 'wifi' && handlers.onWifiSearch) {
        const scan = append(form, 'section', {className: 'wifi-scan'});
        const scanHeader = append(scan, 'div', {className: 'wifi-scan-header'});
        append(scanHeader, 'h3', {text: 'Available networks'});
        const search = append(scanHeader, 'button', {text: 'Search', attributes: {type: 'button'}});
        const scanStatus = append(scan, 'p', {className: 'form-note', text: 'Press Search to ask the camera to scan for nearby access points.'});
        const results = append(scan, 'div', {className: 'wifi-scan-results'});
        search.addEventListener('click', async () => {
            search.disabled = true;
            scanStatus.textContent = 'Searching from the camera…';
            results.replaceChildren();
            try {
                const data = await handlers.onWifiSearch();
                const table = append(results, 'table', {className: 'wifi-table'});
                const head = append(table, 'thead');
                const headRow = append(head, 'tr');
                for (const title of ['SSID', 'Authentication', 'Signal', 'Channel', '']) {
                    append(headRow, 'th', {text: title});
                }
                const body = append(table, 'tbody');
                for (const accessPoint of data.accessPoints) {
                    const row = append(body, 'tr');
                    append(row, 'td', {text: accessPoint.ssid || '(hidden)'});
                    append(row, 'td', {text: `${accessPoint.authentication} / ${accessPoint.encryption}`});
                    append(row, 'td', {text: accessPoint.signal});
                    append(row, 'td', {text: accessPoint.channel});
                    const action = append(row, 'td');
                    const use = append(action, 'button', {text: 'Use', attributes: {type: 'button'}});
                    use.addEventListener('click', () => {
                        const current = servicePayloadFromFormData(model, new FormData(form));
                        const selected = wifiValuesFromAccessPoint(current, accessPoint);
                        for (const name of ['ssid', 'auth', 'encryption', 'channel', 'networkType']) {
                            const input = form.elements.namedItem(name);
                            if (input) input.value = selected[name];
                        }
                        scanStatus.textContent = `Selected ${accessPoint.ssid || 'hidden network'}. Enter its password if required.`;
                    });
                }
                scanStatus.textContent = `Found ${data.count} network${data.count === 1 ? '' : 's'}.`;
            } catch (error) {
                scanStatus.textContent = error?.message || 'Wi-Fi search failed.';
            } finally {
                search.disabled = false;
            }
        });
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
    const ptzSection = model.id === 'human_detection' && model.fields.ptzAction
        ? append(form, 'details', {className: 'form-subsection'})
        : null;
    if (ptzSection) {
        append(ptzSection, 'summary', {text: 'PTZ activation'});
        append(ptzSection, 'p', {
            className: 'form-note',
            text: 'Requires an existing saved preset, tour, or pattern on a motorized camera. The camera’s generic preset list does not confirm that one is saved.',
        });
    }
    const ptzGrid = ptzSection ? append(ptzSection, 'div', {className: 'form-grid'}) : null;
    const controls = new Map();
    let ptzNumberLabel = null;
    let ptzNumberHelp = null;
    for (const [name, field] of entries) {
        if (enableEntry?.[0] === name) continue;
        if (model.id === 'human_detection' && model.ruleGeometry && ['ruleEnabled', 'ruleType'].includes(name)) continue;
        const fieldParent = ptzGrid && ['ptzAction', 'ptzNumber'].includes(name) ? ptzGrid : grid;
        if (isSwitch(field)) {
            const label = append(fieldParent, 'label', {className: 'form-switch-field'});
            const input = append(label, 'input', {attributes: {type: 'checkbox', name}});
            input.checked = Boolean(model.values[name]);
            controls.set(name, input);
            append(label, 'span', {text: field.label});
            continue;
        }
        const label = append(fieldParent, 'label', {className: 'form-field'});
        append(label, 'span', {className: 'form-label', text: field.label});
        if (field.options) {
            const select = append(label, 'select', {className: 'form-input', attributes: {name}});
            for (const value of field.options) {
                const option = append(select, 'option', {
                    text: serviceOptionLabel(field, value),
                    attributes: {value},
                });
                option.selected = String(value) === String(model.values[name]);
            }
            select.value = String(model.values[name] ?? '');
            controls.set(name, select);
            if (field.help) append(label, 'small', {className: 'form-help', text: field.help});
            continue;
        }
        const attributes = {name, type: inputTypeForServiceField(field)};
        if (field.min !== undefined) attributes.min = String(field.min);
        if (field.max !== undefined) attributes.max = String(field.max);
        if (field.required) attributes.required = '';
        const input = append(label, 'input', {className: 'form-input', attributes});
        input.value = model.values[name] ?? '';
        controls.set(name, input);
        if (name === 'ptzNumber' && ptzGrid) {
            ptzNumberLabel = label;
            ptzNumberHelp = append(label, 'small', {className: 'form-help'});
        }
    }

    if (controls.has('ptzAction') && controls.has('ptzNumber') && ptzNumberLabel) {
        const action = controls.get('ptzAction');
        const number = controls.get('ptzNumber');
        const help = {
            Preset: 'Enter the saved preset number from the camera’s PTZ settings. A preset is one stored view; use its exact slot number.',
            Tour: 'Enter the saved tour number from the camera’s PTZ settings. A tour visits stored views in sequence; use its exact slot number.',
            Pattern: 'Enter the saved pattern number from the camera’s PTZ settings. Record a PTZ movement path there first; Balcon pattern playback has not been verified.',
        };
        const refreshPtzNumber = (changed) => {
            const inactive = action.value === 'None';
            ptzNumberLabel.hidden = inactive;
            number.disabled = inactive;
            if (inactive) number.value = '0';
            else if (changed && Number(number.value) < 1) number.value = '';
            const legacyZero = !changed && action.value === model.values.ptzAction
                && model.values.ptzNumber === 0;
            number.setAttribute('min', legacyZero || inactive ? '0' : '1');
            if (!inactive) ptzNumberHelp.textContent = `${help[action.value]} This app cannot verify which slots are saved.`;
        };
        action.addEventListener('change', () => refreshPtzNumber(true));
        refreshPtzNumber(false);
    }

    const ruleSection = model.id === 'human_detection' && model.ruleGeometry && handlers.cameraId
        ? renderRuleSection(form, model, handlers.cameraId)
        : null;
    const periodSection = model.id === 'human_detection' && model.periods
        ? renderPeriodSection(form, model.periods)
        : null;

    const refreshDisabledFields = () => {
        const values = {...model.values};
        for (const [name, input] of controls) {
            const field = model.fields[name];
            if (field.type === 'boolean') values[name] = input.checked;
        }
        for (const [name, input] of controls) {
            input.disabled = Boolean(isServiceFieldDisabled(model.fields[name], values)
                || (name === 'ptzNumber' && controls.get('ptzAction')?.value === 'None'));
        }
    };
    for (const [name, input] of controls) {
        if (model.fields[name].type === 'boolean') input.addEventListener('change', refreshDisabledFields);
    }
    refreshDisabledFields();

    const note = append(form, 'p', {
        className: 'form-note',
        text: model.id === 'human_detection'
            ? 'Saving fresh-reads the detection and shared alert-action branches, merges only changed schedule slots and these controls, then verifies each written branch.'
            : `Saving reads the latest ${model.branch} object, merges only these fields, writes that complete branch, then reads it back and verifies it.`,
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
    if (model.id === 'ftp' && handlers.onFtpTest) {
        const testResult = append(form, 'p', {className: 'connection-status ftp-test-result'});
        testResult.setAttribute('role', 'status');
        testResult.setAttribute('aria-live', 'polite');
        const test = append(actions, 'button', {
            text: 'Test FTP connection', attributes: {type: 'button'},
        });
        test.addEventListener('click', async () => {
            test.disabled = true;
            testResult.textContent = 'Testing saved FTP settings…';
            testResult.dataset.kind = 'neutral';
            try {
                const outcome = await handlers.onFtpTest();
                if (outcome) {
                    testResult.textContent = outcome.message;
                    testResult.dataset.kind = outcome.ok ? 'ok' : 'error';
                }
            } catch (error) {
                testResult.textContent = error?.message || 'FTP test failed.';
                testResult.dataset.kind = 'error';
            } finally {
                test.disabled = false;
            }
        });
        const testNote = append(form, 'p', {
            className: 'form-note',
            text: 'Tests the camera’s saved FTP settings. Unsaved edits are not used or saved; a successful test does not prove that event uploads are enabled.',
        });
        testNote.setAttribute('role', 'note');
    }
    append(actions, 'button', {className: 'primary-button', text: 'Review changes', attributes: {type: 'submit'}});

    let pending = null;
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        pending = servicePayloadFromFormData(model, new FormData(form));
        if (ruleSection) {
            const changes = ruleSection.collectChanges();
            if (changes === null) return;
            pending.ruleGeometryChanges = changes;
        }
        if (periodSection) {
            const changes = periodSection.collectChanges();
            if (changes === null) return;
            pending.periodChanges = changes;
        }
        list.replaceChildren();
        for (const [name, field] of entries) {
            if (name === 'ptzNumber' && pending.ptzAction === 'None') continue;
            let display = pending[name];
            if (isSwitch(field)) display = display ? 'Enabled' : 'Disabled';
            else if (field.options) display = serviceOptionLabel(field, String(display));
            if (display === '') display = '(empty)';
            append(list, 'li', {text: `${field.label}: ${display}`});
        }
        for (const change of pending.periodChanges || []) {
            append(list, 'li', {text: periodSection.describe(change)});
        }
        if (ruleSection && Object.keys(pending.ruleGeometryChanges).length) {
            append(list, 'li', {text: ruleSection.describe(pending.ruleGeometryChanges)});
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
