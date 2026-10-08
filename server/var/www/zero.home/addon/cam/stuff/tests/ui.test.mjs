import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import test from 'node:test';

const moduleUrl = async (name) => {
    const path = new URL(`../js/${name}`, import.meta.url);
    let source;
    try {
        source = await readFile(path, 'utf8');
    } catch {
        throw new Error(`browser module ${name} does not exist`);
    }
    return path.href;
};

class FakeNode {
    constructor(type, value = '', ownerDocument = null) {
        this.type = type;
        this.value = value;
        this.ownerDocument = ownerDocument;
        this.children = [];
        this.className = '';
        this.open = false;
        this.dataset = {};
        this.attributes = {};
        this.listeners = {};
        this.innerHtmlWasSet = false;
    }

    append(...nodes) {
        this.children.push(...nodes);
    }

    setAttribute(name, value) {
        this.attributes[name] = value;
    }

    addEventListener(name, callback) {
        this.listeners[name] = callback;
    }

    set textContent(value) {
        this.children = [new FakeNode('text', String(value))];
    }

    set innerHTML(value) {
        this.innerHtmlWasSet = true;
        throw new Error(`innerHTML forbidden: ${value}`);
    }
}

class FakeDocument {
    createElement(name) {
        return new FakeNode(name, '', this);
    }

    createTextNode(value) {
        return new FakeNode('text', String(value));
    }
}

const descendants = (node) => [node, ...node.children.flatMap(descendants)];

const response = (data, ok = true, status = 200) => ({
    ok,
    status,
    async json() {
        return ok
            ? {ok: true, data, error: null}
            : {ok: false, data: null, error: data};
    },
});

test('camera API normalizes camera list and default', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    const api = new CameraApi('/api.php', async () => response({
        cameras: [{id: 'balcon', name: 'Balcon'}],
        default: 'balcon',
    }));
    assert.deepEqual(await api.listCameras(), {
        cameras: [{id: 'balcon', name: 'Balcon'}],
        default: 'balcon',
    });
});

test('state chooses Balcon default when present', async () => {
    const {chooseDefaultCamera} = await import(await moduleUrl('state.js'));
    assert.equal(chooseDefaultCamera({
        cameras: [{id: 'balcon', name: 'Balcon'}],
        default: 'balcon',
    }), 'balcon');
});

test('section menu marks exactly one item as current', async () => {
    const {markActiveSection} = await import(await moduleUrl('state.js'));
    const makeButton = (sectionId) => ({
        dataset: {sectionId},
        classList: {
            values: new Set(),
            toggle(name, enabled) {
                if (enabled) this.values.add(name);
                else this.values.delete(name);
            },
            contains(name) {
                return this.values.has(name);
            },
        },
        attributes: new Map(),
        setAttribute(name, value) { this.attributes.set(name, value); },
        removeAttribute(name) { this.attributes.delete(name); },
    });
    const buttons = [makeButton('network'), makeButton('camera')];

    markActiveSection(buttons, 'camera');

    assert.equal(buttons[0].classList.contains('is-active'), false);
    assert.equal(buttons[0].attributes.has('aria-current'), false);
    assert.equal(buttons[1].classList.contains('is-active'), true);
    assert.equal(buttons[1].attributes.get('aria-current'), 'page');
});

test('section menu buttons follow camera request busy state', async () => {
    const {setSectionButtonsBusy} = await import(await moduleUrl('state.js'));
    const buttons = [{disabled: false}, {disabled: false}];
    setSectionButtonsBusy(buttons, true);
    assert.deepEqual(buttons.map((button) => button.disabled), [true, true]);
    setSectionButtonsBusy(buttons, false);
    assert.deepEqual(buttons.map((button) => button.disabled), [false, false]);
});

test('editable menu shows supported forms in the requested groups and order', async () => {
    const {renderEditableMenu} = await import(await moduleUrl('menu.js'));
    const nodes = renderEditableMenu([
        {id: 'ftp', name: 'FTP'},
        {id: 'human_detection', name: 'Human detection'},
        {id: 'wifi', name: 'Wi-Fi'},
        {id: 'wired_network', name: 'Wired network'},
    ], new FakeDocument(), {onSmtp() {}, onEditor() {}});

    assert.deepEqual(nodes.map((node) => [node.type, node.children[0]?.value ?? '']), [
        ['h3', 'NETWORK'],
        ['button', 'Wired network'],
        ['button', 'Wi-Fi'],
        ['h3', 'DETECTION'],
        ['button', 'Human detection'],
        ['h3', 'SERVICES 3rd PART'],
        ['button', 'Email / SMTP'],
        ['button', 'FTP'],
    ]);
    assert.deepEqual(nodes.filter((node) => node.type === 'button').map((node) => node.dataset.sectionId), [
        'editor:wired_network', 'editor:wifi', 'editor:human_detection', 'smtp', 'editor:ftp',
    ]);
});

test('editable menu keeps newly discovered forms reachable', async () => {
    const {renderEditableMenu} = await import(await moduleUrl('menu.js'));
    const nodes = renderEditableMenu([
        {id: 'future_camera_option', name: 'Future camera option'},
    ], new FakeDocument(), {onSmtp() {}, onEditor() {}});
    const buttons = nodes.filter((node) => node.type === 'button');
    assert.deepEqual(buttons.map((button) => button.dataset.sectionId), [
        'smtp', 'editor:future_camera_option',
    ]);
});

test('camera API URL-encodes camera and section', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    let requestedUrl = '';
    const api = new CameraApi('/api.php', async (url) => {
        requestedUrl = url;
        return response({});
    });
    await api.config('balcon & roof', 'advanced encoding');
    assert.equal(
        requestedUrl,
        '/api.php?action=config&camera=balcon+%26+roof&section=advanced+encoding'
    );
});

test('camera API exposes response envelope errors', async () => {
    const {CameraApi, CameraApiError} = await import(await moduleUrl('api.js'));
    const api = new CameraApi('/api.php', async () => response(
        {code: 'camera_not_found', message: 'Camera is not configured.'},
        false,
        404
    ));
    await assert.rejects(
        api.status('missing'),
        (error) => error instanceof CameraApiError
            && error.code === 'camera_not_found'
            && error.status === 404
    );
});

test('camera API aborts a superseded request', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    const signals = [];
    const api = new CameraApi('/api.php', async (url, options) => {
        signals.push(options.signal);
        return response({url});
    });
    await api.status('balcon');
    await api.sections('balcon');
    assert.equal(signals[0].aborted, true);
    assert.equal(signals[1].aborted, false);
});

test('camera API posts SMTP changes as JSON', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    let request;
    const api = new CameraApi('/api.php', async (url, options) => {
        request = {url, options};
        return response({server: 'mail.example.test'});
    });
    const smtp = {enable: false, server: 'mail.example.test', port: 25};

    await api.saveSmtp('balcon', smtp);

    assert.equal(request.url, '/api.php?action=smtp-save&camera=balcon');
    assert.equal(request.options.method, 'POST');
    assert.equal(request.options.headers['Content-Type'], 'application/json');
    assert.deepEqual(JSON.parse(request.options.body), smtp);
});

test('camera API posts a separate SMTP test request without configuration values', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    let request;
    const api = new CameraApi('/api.php', async (url, options) => {
        request = {url, options};
        return response({accepted: true});
    });

    assert.deepEqual(await api.testSmtp('door'), {accepted: true});
    assert.equal(request.url, '/api.php?action=smtp-test&camera=door');
    assert.equal(request.options.method, 'POST');
    assert.deepEqual(JSON.parse(request.options.body), {});
});

test('camera API posts a separate FTP test request without configuration values', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    let request;
    const api = new CameraApi('/api.php', async (url, options) => {
        request = {url, options};
        return response({accepted: true});
    });

    assert.deepEqual(await api.testFtp('balcon'), {accepted: true});
    assert.equal(request.url, '/api.php?action=ftp-test&camera=balcon');
    assert.equal(request.options.method, 'POST');
    assert.deepEqual(JSON.parse(request.options.body), {});
});

test('FTP editor renders a test button that invokes its separate test action', async () => {
    const {renderServiceForm} = await import(await moduleUrl('service-form.js'));
    let tested = 0;
    const model = {
        id: 'ftp', branch: 'NetWork.NetFTP', label: 'FTP', warning: null,
        fields: {
            enable: {label: 'FTP uploads', type: 'boolean'},
            server: {label: 'Server', type: 'string'},
        },
        values: {enable: true, server: 'ftp.example.test'},
    };
    const handlers = {
        cameraId: 'balcon', onReload: () => {}, onSave: async () => {},
        onFtpTest: async () => { tested++; },
    };
    const root = renderServiceForm(model, handlers, new FakeDocument());
    const button = descendants(root).find((node) =>
        node.type === 'button' && descendants(node).some((child) =>
            child.type === 'text' && child.value === 'Test FTP connection'
        )
    );
    assert.ok(button);
    await button.listeners.click();
    assert.equal(tested, 1);
});

test('human detection keeps PTZ activation in a collapsed section when camera offers it', async () => {
    const {renderServiceForm} = await import(await moduleUrl('service-form.js'));
    const model = {
        id: 'human_detection', branch: 'Detect.HumanDetection', label: 'Human detection',
        fields: {
            enable: {label: 'Human detection', type: 'boolean'},
            ptzAction: {label: 'PTZ activation action', type: 'string', options: ['None', 'Preset', 'Tour', 'Pattern']},
            ptzNumber: {label: 'Saved PTZ action number', type: 'integer', min: 0, required: true},
        },
        values: {enable: true, ptzAction: 'None', ptzNumber: 0},
    };
    const root = renderServiceForm(model, {cameraId: 'balcon', onReload: () => {}, onSave: async () => {}}, new FakeDocument());
    const ptz = descendants(root).find((node) => node.type === 'details'
        && descendants(node).some((child) => child.value === 'PTZ activation'));
    assert.ok(ptz);
    assert.equal(ptz.attributes.open, undefined);
    assert.ok(descendants(ptz).some((node) => node.attributes.name === 'ptzAction'));
    assert.ok(descendants(ptz).some((node) => node.attributes.name === 'ptzNumber'));
    const action = descendants(ptz).find((node) => node.attributes.name === 'ptzAction');
    const number = descendants(ptz).find((node) => node.attributes.name === 'ptzNumber');
    const numberLabel = descendants(ptz).find((node) => node.type === 'label' && descendants(node).includes(number));
    assert.equal(numberLabel.hidden, true);
    assert.equal(number.disabled, true);
    action.value = 'Preset';
    action.listeners.change();
    assert.equal(numberLabel.hidden, false);
    assert.equal(number.disabled, false);
    assert.equal(number.value, '');
    assert.equal(number.attributes.min, '1');
    assert.equal(number.attributes.required, '');
    const help = descendants(numberLabel).find((node) => node.type === 'small');
    assert.match(help.children[0].value, /saved preset number/i);
    action.value = 'Pattern';
    action.listeners.change();
    assert.match(help.children[0].value, /saved pattern number/i);
    assert.match(help.children[0].value, /record.*PTZ/i);
    action.value = 'None';
    action.listeners.change();
    assert.equal(numberLabel.hidden, true);
    assert.equal(number.disabled, true);
    assert.equal(number.value, '0');
});

test('existing PTZ Pattern zero stays visible for correction rather than hiding the editor', async () => {
    const {renderServiceForm} = await import(await moduleUrl('service-form.js'));
    const model = {
        id: 'human_detection', branch: 'Detect.HumanDetection', label: 'Human detection',
        fields: {
            enable: {label: 'Human detection', type: 'boolean'},
            ptzAction: {label: 'PTZ activation action', type: 'string', options: ['None', 'Preset', 'Tour', 'Pattern']},
            ptzNumber: {label: 'Saved PTZ action number', type: 'integer', min: 0, required: true},
        },
        values: {enable: true, ptzAction: 'Pattern', ptzNumber: 0},
    };
    const root = renderServiceForm(model, {cameraId: 'balcon', onReload: () => {}, onSave: async () => {}}, new FakeDocument());
    const number = descendants(root).find((node) => node.attributes.name === 'ptzNumber');
    const numberLabel = descendants(root).find((node) => node.type === 'label' && descendants(node).includes(number));
    assert.equal(numberLabel.hidden, false);
    assert.equal(number.value, 0);
    assert.equal(number.attributes.min, '0');
});


test('FTP editor shows the camera test outcome beside the button', async () => {
    const {renderServiceForm} = await import(await moduleUrl('service-form.js'));
    const model = {
        id: 'ftp', branch: 'NetWork.NetFTP', label: 'FTP', warning: null,
        fields: {enable: {label: 'FTP uploads', type: 'boolean'}},
        values: {enable: true},
    };
    let outcome = {ok: false, message: 'Camera reported the FTP test failed.'};
    const root = renderServiceForm(model, {
        cameraId: 'balcon', onReload: () => {}, onSave: async () => {},
        onFtpTest: async () => outcome,
    }, new FakeDocument());
    const button = descendants(root).find((node) => node.type === 'button'
        && descendants(node).some((child) => child.value === 'Test FTP connection'));
    await button.listeners.click();
    const result = descendants(root).find((node) => node.attributes.role === 'status');
    assert.ok(result);
    assert.equal(result.children[0].value, 'Camera reported the FTP test failed.');
    assert.equal(result.dataset.kind, 'error');

    outcome = {ok: true, message: 'Camera accepted the FTP test request.'};
    await button.listeners.click();
    assert.equal(result.children[0].value, 'Camera accepted the FTP test request.');
    assert.equal(result.dataset.kind, 'ok');
});

test('SMTP form payload submits blank password to clear it and removes empty recipients', async () => {
    const {smtpPayloadFromFormData} = await import(await moduleUrl('smtp-form.js'));
    const fields = new FormData();
    fields.set('enable', 'on');
    fields.set('server', ' mail.example.test ');
    fields.set('port', '587');
    fields.set('username', 'mailer');
    fields.set('password', '');
    fields.set('useSsl', '1');
    fields.set('sendAddress', 'camera@example.test');
    fields.append('recipients', 'one@example.test');
    fields.append('recipients', '   ');
    fields.append('recipients', 'two@example.test');
    fields.set('title', 'Balcon alert');

    assert.deepEqual(smtpPayloadFromFormData(fields), {
        enable: true,
        server: 'mail.example.test',
        port: 587,
        username: 'mailer',
        password: '',
        useSsl: 1,
        sendAddress: 'camera@example.test',
        recipients: ['one@example.test', 'two@example.test'],
        title: 'Balcon alert',
    });
});

test('camera API reads and posts a named network editor', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    const requests = [];
    const api = new CameraApi('/api.php', async (url, options) => {
        requests.push({url, options});
        return response({id: 'ftp'});
    });

    await api.editor('balcon', 'ftp');
    await api.saveEditor('balcon', 'ftp', {enable: false});

    assert.equal(requests[0].url, '/api.php?action=editor&camera=balcon&editor=ftp');
    assert.equal(requests[0].options.method, 'GET');
    assert.equal(requests[1].url, '/api.php?action=editor-save&camera=balcon&editor=ftp');
    assert.equal(requests[1].options.method, 'POST');
    assert.deepEqual(JSON.parse(requests[1].options.body), {enable: false});
});

test('camera API requests the Balcon Wi-Fi access-point scan', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    let requestedUrl = '';
    const api = new CameraApi('/api.php', async (url) => {
        requestedUrl = url;
        return response({count: 0, accessPoints: []});
    });
    await api.wifiAccessPoints('balcon');
    assert.equal(requestedUrl, '/api.php?action=wifi-aps&camera=balcon');
});

test('camera API posts the reboot action', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    let request = null;
    const api = new CameraApi('/api.php', async (url, options) => {
        request = {url, options};
        return response({accepted: true});
    });

    await api.reboot('balcon');
    assert.equal(request.url, '/api.php?action=reboot&camera=balcon');
    assert.equal(request.options.method, 'POST');
    assert.deepEqual(JSON.parse(request.options.body), {confirm: true});
});

test('Wi-Fi access-point selection populates compatible form values', async () => {
    const {wifiValuesFromAccessPoint} = await import(await moduleUrl('service-form.js'));
    assert.deepEqual(wifiValuesFromAccessPoint(
        {enable: true, ssid: 'old', auth: 'Code 1', encryption: 'Code 6', channel: 0, networkType: 'Infra'},
        {ssid: 'q001', authentication: 'WPA2PSK', encryption: 'AES', channel: 8, networkType: 'Infra'}
    ), {
        enable: true, ssid: 'q001', auth: 'WPA2PSK', encryption: 'AES', channel: 8, networkType: 'Infra',
    });
});

test('network service form payload submits blank password to clear it', async () => {
    const {servicePayloadFromFormData} = await import(await moduleUrl('service-form.js'));
    const model = {
        fields: {
            enable: {type: 'boolean'},
            server: {type: 'string'},
            port: {type: 'integer'},
            password: {type: 'password'},
        },
    };
    const fields = new FormData();
    fields.set('enable', 'on');
    fields.set('server', ' ftp.example.test ');
    fields.set('port', '2121');
    fields.set('password', '');

    assert.deepEqual(servicePayloadFromFormData(model, fields), {
        enable: true,
        server: 'ftp.example.test',
        port: 2121,
        password: '',
    });
});

test('network service payload preserves disabled values omitted by FormData', async () => {
    const {servicePayloadFromFormData} = await import(await moduleUrl('service-form.js'));
    const model = {
        fields: {
            dhcp: {type: 'boolean'},
            keyType: {type: 'integer', disabled: true},
            hostIp: {type: 'ipv4-little-endian', disableWhen: 'dhcp'},
        },
        values: {dhcp: true, keyType: 1, hostIp: '192.168.1.112'},
    };
    const fields = new FormData();
    fields.set('dhcp', 'on');

    assert.deepEqual(servicePayloadFromFormData(model, fields), {
        dhcp: true,
        keyType: 1,
        hostIp: '192.168.1.112',
    });
});

test('Wi-Fi static fields are disabled by DHCP while key type is always locked', async () => {
    const {isServiceFieldDisabled} = await import(await moduleUrl('service-form.js'));
    assert.equal(isServiceFieldDisabled({disabled: true}, {dhcp: false}), true);
    assert.equal(isServiceFieldDisabled({disableWhen: 'dhcp'}, {dhcp: true}), true);
    assert.equal(isServiceFieldDisabled({disableWhen: 'dhcp'}, {dhcp: false}), false);
});

test('service select labels hide backend values from humans', async () => {
    const {serviceOptionLabel} = await import(await moduleUrl('service-form.js'));
    const field = {optionLabels: {
        WPA2PSK: 'WPA2 Personal (password)',
        'Code 9': 'Unknown authentication value reported by camera',
    }};
    assert.equal(serviceOptionLabel(field, 'WPA2PSK'), 'WPA2 Personal (password)');
    assert.equal(serviceOptionLabel(field, 'Code 9'), 'Unknown authentication value reported by camera');
    assert.equal(serviceOptionLabel({}, 'AES'), 'AES');
});

test('human detection rule mode labels map verified DVRIP values to readable options', async () => {
    const {serviceOptionLabel, servicePayloadFromFormData} = await import(await moduleUrl('service-form.js'));
    const model = {fields: {
        ruleType: {
            type: 'integer', options: ['0', '1'],
            optionLabels: {'0': 'Tripwire', '1': 'Intrusion area'},
        },
    }};
    assert.equal(serviceOptionLabel(model.fields.ruleType, '0'), 'Tripwire');
    assert.equal(serviceOptionLabel(model.fields.ruleType, '1'), 'Intrusion area');
    const formData = new FormData();
    formData.set('ruleType', '0');
    assert.deepEqual(servicePayloadFromFormData(model, formData), {ruleType: 0});
});

test('rule coordinates follow actual image bounds, independent of camera resolution', async () => {
    const {normalizedPoint, moveRegionEdge} = await import(await moduleUrl('rule-geometry.js'));
    assert.deepEqual(normalizedPoint({clientX: 500, clientY: 274}, {left: 100, top: 50, width: 800, height: 448}), {X: 4096, Y: 4096});
    assert.deepEqual(normalizedPoint({clientX: 900, clientY: 498}, {left: 100, top: 50, width: 1600, height: 896}), {X: 4096, Y: 4096});
    const area = [{X: 100, Y: 100}, {X: 8000, Y: 100}, {X: 8000, Y: 8000}, {X: 100, Y: 8000}];
    assert.deepEqual(moveRegionEdge(area, 0, 0, 200), [{X: 100, Y: 300}, {X: 8000, Y: 300}, area[2], area[3]]);
});

test('editable password controls are visible text fields', async () => {
    const {inputTypeForServiceField} = await import(await moduleUrl('service-form.js'));
    const {inputTypeForSmtpField} = await import(await moduleUrl('smtp-form.js'));
    assert.equal(inputTypeForServiceField({type: 'password'}), 'text');
    assert.equal(inputTypeForServiceField({type: 'integer'}), 'number');
    assert.equal(inputTypeForSmtpField('password'), 'text');
});

test('network service form payload converts integer-backed switches', async () => {
    const {servicePayloadFromFormData} = await import(await moduleUrl('service-form.js'));
    const model = {fields: {
        enabled: {type: 'boolean-integer'},
        autoCheck: {type: 'boolean-integer'},
    }};
    const fields = new FormData();
    fields.set('enabled', 'on');
    assert.deepEqual(servicePayloadFromFormData(model, fields), {
        enabled: 1,
        autoCheck: 0,
    });
});

test('tree renderer recursively uses text nodes for camera values', async () => {
    const {renderTree} = await import(await moduleUrl('tree.js'));
    const malicious = '<img src=x onerror=alert(1)>';
    const tree = renderTree({Nested: [true, 2, malicious]}, new FakeDocument());
    const serialized = JSON.stringify(tree);
    assert.match(serialized, /<img src=x onerror=alert\(1\)>/);
    assert.equal(tree.innerHtmlWasSet, false);
    assert.equal(tree.type, 'dl');
});

test('selection guard rejects stale response after second connect', async () => {
    const {SelectionGuard} = await import(await moduleUrl('state.js'));
    const guard = new SelectionGuard();
    const first = guard.begin('balcon');
    const second = guard.begin('balcon');
    assert.equal(guard.isCurrent(first), false);
    assert.equal(guard.isCurrent(second), true);
});

test('configuration tree is constrained inside the content panel', async () => {
    const css = await readFile(new URL('../css/app.css', import.meta.url), 'utf8');
    assert.match(css, /\.content-panel\s*\{[^}]*min-width:\s*0/s);
    assert.match(css, /\.section-content\s*\{[^}]*overflow-x:\s*auto/s);
    assert.match(css, /\.tree-list\s*\{[^}]*width:\s*100%/s);
});

test('current section has persistent menu styling', async () => {
    const css = await readFile(new URL('../css/app.css', import.meta.url), 'utf8');
    assert.match(css, /\.section-button\.is-active\s*\{/);
});

test('menu group headers stay static and read-only settings have a larger gap', async () => {
    const css = await readFile(new URL('../css/app.css', import.meta.url), 'utf8');
    assert.match(css, /\.section-group-heading\s*\{/);
    assert.match(css, /\.section-divider\s*\{[^}]*margin:\s*2rem\s+-\.45rem\s+\.25rem/s);
});
