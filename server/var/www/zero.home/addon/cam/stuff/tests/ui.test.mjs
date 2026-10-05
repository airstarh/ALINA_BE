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
    return `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`;
};

class FakeNode {
    constructor(type, value = '') {
        this.type = type;
        this.value = value;
        this.children = [];
        this.className = '';
        this.open = false;
        this.dataset = {};
        this.innerHtmlWasSet = false;
    }

    append(...nodes) {
        this.children.push(...nodes);
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
        return new FakeNode(name);
    }

    createTextNode(value) {
        return new FakeNode('text', String(value));
    }
}

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

test('SMTP form payload keeps blank password and removes empty recipients', async () => {
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

test('network service form payload preserves schema types and blank password', async () => {
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
