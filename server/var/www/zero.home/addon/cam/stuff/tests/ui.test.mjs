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
        this.hidden = false;
        this.classList = {
            toggle: (name, force) => {
                const names = new Set(this.className.split(/\s+/).filter(Boolean));
                const enabled = force === undefined ? !names.has(name) : force;
                enabled ? names.add(name) : names.delete(name);
                this.className = [...names].join(' ');
            },
        };
    }

    append(...nodes) {
        this.children.push(...nodes);
    }

    replaceChildren(...nodes) { this.children = [...nodes]; }

    setAttribute(name, value) {
        this.attributes[name] = value;
    }

    addEventListener(name, callback) {
        this.listeners[name] = callback;
    }

    removeEventListener(name, callback) {
        if (this.listeners[name] === callback) delete this.listeners[name];
    }

    removeAttribute(name) { delete this.attributes[name]; }
    querySelectorAll(selector) {
        if (!selector.startsWith('.')) return [];
        const name = selector.slice(1);
        return descendants(this).filter(node => node !== this && node.className.split(/\s+/).includes(name));
    }
    pause() { this.pauseCalls = (this.pauseCalls || 0) + 1; }
    load() { this.loadCalls = (this.loadCalls || 0) + 1; }
    async play() { this.playCalls = (this.playCalls || 0) + 1; }

    set textContent(value) {
        this.children = [new FakeNode('text', String(value))];
    }

    set innerHTML(value) {
        this.innerHtmlWasSet = true;
        throw new Error(`innerHTML forbidden: ${value}`);
    }
}

class FakeDocument {
    constructor() {
        this.listeners = {};
        this.hidden = false;
        this.nodes = new Map();
        Object.defineProperty(this, 'body', {value: this.createElement('body'), enumerable: false});
    }

    createElement(name) {
        return new FakeNode(name, '', this);
    }

    createTextNode(value) {
        return new FakeNode('text', String(value));
    }

    addEventListener(name, callback) { this.listeners[name] = callback; }
    removeEventListener(name, callback) {
        if (this.listeners[name] === callback) delete this.listeners[name];
    }

    querySelector(selector) { return this.nodes.get(selector) || null; }
}

const descendants = (node) => [node, ...node.children.flatMap(descendants)];
const flushMedia = async () => { await new Promise(resolve => setImmediate(resolve)); };

test('STREAM menu places Snapshot image directly after Extra video stream', async () => {
    const {renderEditableMenu} = await import(await moduleUrl('menu.js'));
    const editors = [
        {id: 'main_stream', name: 'Main video stream'},
        {id: 'extra_stream', name: 'Extra video stream'},
        {id: 'snapshot_image', name: 'Snapshot image'},
        {id: 'smart_h264', name: 'Smart H.264'},
    ];
    const nodes = renderEditableMenu(editors, new FakeDocument(), {onAccess() {}, onSmtp() {}, onEditor() {}, onLive() {}});
    const stream = descendants({children: nodes}).find(node => node.type === 'summary' && node.children[0]?.value === 'STREAM');
    const group = nodes.find(node => descendants(node).includes(stream));
    const labels = descendants(group).filter(node => node.type === 'button').map(node => node.children[0]?.value);
    assert.deepEqual(labels, ['Main video stream', 'Extra video stream', 'Snapshot image', 'Smart H.264']);
});

test('snapshot image form groups independent profiles without a synchronization control', async () => {
    const {renderServiceForm} = await import(await moduleUrl('service-form.js'));
    const fields = {};
    const values = {};
    for (const [prefix, group, enabled] of [
        ['regular', 'Regular snapshot', true],
        ['motion', 'Motion-detection snapshot', false],
        ['alarm', 'Alarm snapshot', false],
    ]) {
        fields[`${prefix}Enabled`] = {label: 'Enabled', type: 'boolean', group};
        fields[`${prefix}Resolution`] = {label: 'Resolution', type: 'string', group, options: ['D1', '1080P']};
        fields[`${prefix}Quality`] = {label: 'Quality', type: 'integer', group, options: ['1', '2', '3', '4', '5', '6']};
        fields[`${prefix}BitRate`] = {label: 'Bit rate (kbps)', type: 'integer', group};
        fields[`${prefix}BitRateControl`] = {label: 'Bit-rate control', type: 'string', group, options: ['CBR', 'VBR']};
        values[`${prefix}Enabled`] = enabled;
        values[`${prefix}Resolution`] = 'D1';
        values[`${prefix}Quality`] = 4;
        values[`${prefix}BitRate`] = 512;
        values[`${prefix}BitRateControl`] = 'VBR';
    }
    const root = renderServiceForm({id: 'snapshot_image', label: 'Snapshot image', branch: 'AVEnc.Encode', fields, values},
        {onReload() {}, onSave() {}}, new FakeDocument());
    const nodes = descendants(root);
    assert.deepEqual(nodes.filter(node => node.type === 'legend').map(node => node.children[0]?.value),
        ['Regular snapshot', 'Motion-detection snapshot', 'Alarm snapshot']);
    assert.equal(nodes.filter(node => node.type === 'input' && node.attributes.name?.endsWith('Enabled')).length, 3);
    assert.equal(nodes.find(node => node.type === 'input' && node.attributes.name === 'synchronizeSnapshotProfiles'), undefined);
});

test('ACCESS Live menu item opens the inline player callback', async () => {
    const {renderEditableMenu} = await import(await moduleUrl('menu.js'));
    let opened = false;
    const nodes = renderEditableMenu([], new FakeDocument(), {onAccess() {}, onSmtp() {}, onEditor() {}, onLive() { opened = true; }});
    const access = nodes[0];
    const live = descendants(access).find(n => n.type === 'button' && n.children[0]?.value === 'Live');
    assert.ok(live);
    live.listeners.click();
    assert.equal(opened, true);
});

test('home dashboard renders every configured camera with compact live controls', async () => {
    const {renderHomeForm} = await import(await moduleUrl('home-form.js'));
    const opened = [];
    const environment = {
        MediaSource: null,
        fetch: async () => ({ok: true, async json() {
            return {ok: true, recording: {active: false, stopping: false}};
        }}),
        addEventListener() {},
        removeEventListener() {},
        setInterval() { return 21; },
        clearInterval() {},
    };
    const view = renderHomeForm({
        cameras: [
            {id: 'balcon', name: 'Balcon', talkback: true},
            {id: 'door', name: 'Door', talkback: false},
        ],
        prepareEndpoint: '/live-stream.php',
        recordEndpoint: '/live-stream.php',
        snapshotEndpoint: '/live-stream.php',
        requestTalkToken: async () => ({token: 'fixture'}),
        onGoLive: cameraId => opened.push(cameraId),
    }, new FakeDocument(), environment);
    await flushMedia();

    const nodes = descendants(view.element);
    assert.equal(nodes.filter(node => node.className === 'home-camera-card').length, 2);
    assert.deepEqual(nodes.filter(node => node.type === 'h3').map(node => node.children[0]?.value), ['Balcon', 'Door']);
    assert.equal(nodes.filter(node => node.type === 'video').length, 2);
    assert.equal(nodes.filter(node => node.children[0]?.value === 'RECORD').length, 2);
    assert.equal(nodes.filter(node => node.children[0]?.value === 'PrintScreen').length, 2);
    assert.equal(nodes.filter(node => node.attributes['aria-label'] === 'Start camera talkback').length, 1);
    assert.equal(nodes.find(node => node.children[0]?.value === 'Start live video'), undefined);
    assert.equal(nodes.find(node => node.className === 'live-ptz'), undefined);

    const goLive = nodes.filter(node => node.type === 'button' && node.children[0]?.value === 'Go Live');
    assert.equal(goLive.length, 2);
    goLive[1].listeners.click();
    assert.deepEqual(opened, ['door']);
    view.destroy();
});

test('camera application keeps Home menu-free and collapses camera navigation on narrow screens', async () => {
    const documentRef = new FakeDocument();
    for (const [selector, type] of [
        ['#home-button', 'button'], ['#camera-form', 'form'], ['#camera-select', 'select'],
        ['#connect-button', 'button'], ['#reboot-button', 'button'], ['#connection-status', 'p'],
        ['#dashboard', 'section'], ['#camera-identity', 'div'], ['#section-list', 'div'],
        ['#section-title', 'h2'], ['#section-content', 'div'], ['#refresh-section', 'button'],
        ['#load-all', 'button'], ['#camera-sidebar', 'aside'], ['#sidebar-toggle', 'button'],
    ]) documentRef.nodes.set(selector, documentRef.createElement(type));
    documentRef.nodes.get('#dashboard').hidden = true;
    documentRef.nodes.get('#sidebar-toggle').hidden = true;
    const requests = [];
    const original = {
        document: globalThis.document, window: globalThis.window, fetch: globalThis.fetch,
        MediaSource: globalThis.MediaSource, setInterval: globalThis.setInterval, clearInterval: globalThis.clearInterval,
    };
    globalThis.document = documentRef;
    globalThis.window = {
        confirm: () => true,
        matchMedia: () => ({matches: true, addEventListener() {}, removeEventListener() {}}),
    };
    globalThis.MediaSource = null;
    globalThis.setInterval = () => 41;
    globalThis.clearInterval = () => {};
    globalThis.fetch = async (url) => {
        requests.push(url);
        if (String(url).includes('action=cameras')) return {ok: true, status: 200, async json() { return {
            ok: true, data: {default: 'balcon', cameras: [
                {id: 'balcon', name: 'Balcon', talkback: true},
                {id: 'door', name: 'Door', talkback: false},
            ]},
        }; }};
        if (String(url).includes('action=status')) return {ok: true, status: 200, async json() {
            return {ok: true, data: {name: 'door'}, error: null};
        }};
        if (String(url).includes('action=editors') || String(url).includes('action=sections')) {
            return {ok: true, status: 200, async json() { return {ok: true, data: [], error: null}; }};
        }
        if (String(url).includes('action=ptz')) return {ok: true, status: 200, async json() {
            return {ok: true, data: {available: false}, error: null};
        }};
        return {ok: true, status: 200, async json() {
            return {ok: true, recording: {active: false, stopping: false}};
        }};
    };

    try {
        await import(`${await moduleUrl('app.js')}?home-test=${Date.now()}`);
        await flushMedia();
        await flushMedia();
        const select = documentRef.nodes.get('#camera-select');
        assert.deepEqual(select.children.map(option => option.value), ['', 'balcon', 'door']);
        assert.equal(select.children[0].children[0].value, 'Select a camera');
        assert.equal(select.value, '');
        assert.equal(documentRef.nodes.get('#dashboard').hidden, false);
        assert.equal(documentRef.nodes.get('#camera-sidebar').hidden, true);
        assert.equal(documentRef.nodes.get('#section-title').children[0].value, 'Home');
        assert.equal(descendants(documentRef.nodes.get('#section-content')).filter(node => node.type === 'video').length, 2);
        assert.ok(requests.some(url => String(url).includes('action=cameras')));
        assert.equal(requests.some(url => String(url).includes('action=status')), false);

        select.value = 'door';
        documentRef.nodes.get('#camera-form').listeners.submit({preventDefault() {}});
        await flushMedia();
        await flushMedia();
        await flushMedia();
        const toggle = documentRef.nodes.get('#sidebar-toggle');
        assert.equal(toggle.hidden, false);
        assert.equal(toggle.attributes['aria-expanded'], 'false');
        assert.equal(toggle.children[0].value, 'Show menu');
        assert.match(documentRef.nodes.get('#dashboard').className, /sidebar-collapsed/);

        toggle.listeners.click();
        assert.equal(toggle.attributes['aria-expanded'], 'true');
        assert.equal(toggle.children[0].value, 'Hide menu');
        assert.doesNotMatch(documentRef.nodes.get('#dashboard').className, /sidebar-collapsed/);

        const liveButton = descendants(documentRef.nodes.get('#section-list'))
            .find(node => node.type === 'button' && node.children[0]?.value === 'Live');
        liveButton.listeners.click();
        assert.equal(toggle.attributes['aria-expanded'], 'false');
        assert.match(documentRef.nodes.get('#dashboard').className, /sidebar-collapsed/);

        documentRef.nodes.get('#home-button').listeners.click();
        assert.equal(toggle.hidden, true);
        assert.equal(select.value, '');
    } finally {
        Object.assign(globalThis, original);
    }
});

test('live page renders an inline Balcon player with explicit start and stop controls', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const view = renderLiveForm({cameraId: 'balcon', endpoint: '/live-stream.php'}, new FakeDocument());
    const nodes = descendants(view.element);
    const video = nodes.find((node) => node.type === 'video');
    const start = nodes.find((node) => node.type === 'button' && node.children[0]?.value === 'Start live video');
    const stop = nodes.find((node) => node.type === 'button' && node.children[0]?.value === 'Stop');

    assert.ok(video);
    assert.equal(video.attributes.autoplay, '');
    assert.equal(video.attributes.muted, '');
    assert.ok(start);
    assert.ok(stop);
    assert.equal(start.disabled, false);
    assert.equal(stop.disabled, true);
    view.destroy();
});

test('live page shows a talk toggle only for a speaker-capable camera', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const balcon = renderLiveForm({
        cameraId: 'balcon', endpoint: '/live-stream.php', talkback: true,
        requestTalkToken: async () => ({token: 'fixture'}),
    }, new FakeDocument());
    const door = renderLiveForm({
        cameraId: 'door', endpoint: '/live-stream.php', talkback: false,
    }, new FakeDocument());

    assert.ok(descendants(balcon.element).find(node => node.attributes['aria-label'] === 'Start camera talkback'));
    assert.equal(descendants(door.element).find(node => node.attributes['aria-label'] === 'Start camera talkback'), undefined);
    balcon.destroy();
    door.destroy();
});

test('live page RECORD button starts and stops server-side recording below the video', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const requests = [];
    const environment = {
        fetch: async (url, options = {}) => {
            requests.push({url, method: options.method || 'GET'});
            const action = new URL(url, 'https://zero.home/').searchParams.get('action');
            const recording = action === 'record-start'
                ? {active: true, stopping: false, filename: '2026-12-31---22-55---balcon.mp4', lengthMax: 300}
                : action === 'record-stop'
                    ? {active: true, stopping: true}
                    : {active: false, stopping: false};
            return {ok: true, async json() { return {ok: true, recording}; }};
        },
        addEventListener() {},
        removeEventListener() {},
        setInterval() { return 17; },
        clearInterval() {},
    };
    const view = renderLiveForm({
        cameraId: 'balcon', endpoint: '/live-stream.php', recordEndpoint: '/live-stream.php',
    }, new FakeDocument(), environment);
    await flushMedia();
    const nodes = descendants(view.element);
    const videoPanel = nodes.find(node => node.className === 'live-video-panel');
    const record = nodes.find(node => node.attributes['aria-label'] === 'Start camera recording');

    assert.ok(record);
    assert.ok(descendants(videoPanel).includes(record));
    assert.equal(record.children[0].value, 'RECORD');
    assert.equal(record.attributes['aria-pressed'], 'false');
    await record.listeners.click();
    assert.equal(record.attributes['aria-pressed'], 'true');
    assert.match(record.className, /is-active/);
    await record.listeners.click();
    assert.deepEqual(requests, [
        {url: '/live-stream.php?camera=balcon&action=record-status', method: 'GET'},
        {url: '/live-stream.php?camera=balcon&action=record-start', method: 'POST'},
        {url: '/live-stream.php?camera=balcon&action=record-stop', method: 'POST'},
    ]);
    view.destroy();
});

test('PrintScreen saves a native camera snapshot and reports its filename', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const requests = [];
    const environment = {
        fetch: async (url, options = {}) => {
            requests.push({url, method: options.method || 'GET'});
            return {ok: true, async json() { return {
                ok: true,
                snapshot: {filename: '2026-12-31---22-55---1798757700123456---door.jpg'},
            }; }};
        },
        addEventListener() {},
        removeEventListener() {},
    };
    const view = renderLiveForm({
        cameraId: 'door', snapshotEndpoint: '/live-stream.php',
    }, new FakeDocument(), environment);
    const nodes = descendants(view.element);
    const button = nodes.find(node => node.attributes['aria-label'] === 'Save camera snapshot');

    assert.ok(button);
    assert.equal(button.children[0].value, 'PrintScreen');
    await button.listeners.click();
    assert.deepEqual(requests, [{
        url: '/live-stream.php?camera=door&action=snapshot-save',
        method: 'POST',
    }]);
    const status = nodes.find(node => node.className === 'snapshot-status');
    assert.equal(status.children[0].value, 'Saved · 2026-12-31---22-55---1798757700123456---door.jpg');
    view.destroy();
});

test('record button discovers recording changes made in another view while inactive', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    let active = false;
    let poll = null;
    const environment = {
        fetch: async () => ({ok: true, async json() {
            return {ok: true, recording: active
                ? {active: true, stopping: false, filename: 'external-door.mp4'}
                : {active: false, stopping: false}};
        }}),
        addEventListener() {},
        removeEventListener() {},
        setInterval(callback) { poll = callback; return 31; },
        clearInterval() {},
    };
    const view = renderLiveForm({
        cameraId: 'door', endpoint: '/live-stream.php', recordEndpoint: '/live-stream.php',
    }, new FakeDocument(), environment);
    await flushMedia();
    const record = descendants(view.element).find(node => node.children[0]?.value === 'RECORD');
    assert.equal(record.attributes['aria-pressed'], 'false');
    assert.equal(typeof poll, 'function');

    active = true;
    await poll();
    await flushMedia();
    assert.equal(record.attributes['aria-pressed'], 'true');
    assert.match(record.className, /is-active/);
    view.destroy();
});

test('live player offers go2rtc only MSE codecs supported by this browser', async () => {
    const {mseCodecList} = await import(await moduleUrl('live-form.js'));
    const codecs = mseCodecList((mime) => /avc1|flac/.test(mime));

    assert.equal(codecs, 'avc1.640029,avc1.64002A,avc1.640033,flac');
});

test('live playback keeps a stable decode cushion while correcting large latency', async () => {
    const {liveEdgeCorrection} = await import(await moduleUrl('live-form.js'));

    assert.equal(liveEdgeCorrection(8.4, 10, 0), 9.7);
    assert.equal(liveEdgeCorrection(8.5, 10, 0), null);
    assert.equal(liveEdgeCorrection(0, 0.1, 0.05), null);
    assert.equal(liveEdgeCorrection(0, 2, 1.8), 1.8);
});

test('live playback waits for one second of media before its initial start', async () => {
    const {hasStartupBuffer} = await import(await moduleUrl('live-form.js'));

    assert.equal(hasStartupBuffer(0, 0.999), false);
    assert.equal(hasStartupBuffer(0, 1), true);
    assert.equal(hasStartupBuffer(4.5, 5.6), true);
});

test('live latency changes use fresh browser module cache keys', async () => {
    const index = await readFile(new URL('../../index.php', import.meta.url), 'utf8');
    const app = await readFile(new URL('../js/app.js', import.meta.url), 'utf8');

    assert.match(index, /app\.js\?v=20261016-2/);
    assert.match(app, /live-form\.js\?v=20261016-2/);
});

test('live page enables configured Door camera playback', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const view = renderLiveForm({cameraId: 'door', endpoint: '/live-stream.php'}, new FakeDocument());
    const nodes = descendants(view.element);
    const start = nodes.find((node) => node.type === 'button' && node.children[0]?.value === 'Start live video');
    const status = nodes.find((node) => node.attributes.role === 'status');

    assert.equal(start.disabled, false);
    assert.equal(status.children[0].value, 'Press Start live video.');
    view.destroy();
});

test('live page starts playback automatically when opened as the connected-camera landing page', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const view = renderLiveForm(
        {cameraId: 'balcon', endpoint: '/live-stream.php', autoStart: true},
        new FakeDocument(),
        {MediaSource: null}
    );
    const status = descendants(view.element).find((node) => node.attributes.role === 'status');

    assert.equal(status.children[0].value, 'This browser does not provide MediaSource video playback.');
    view.destroy();
});

test('Door live page also autostarts when opened as the connected-camera landing page', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const view = renderLiveForm(
        {cameraId: 'door', endpoint: '/live-stream.php', autoStart: true},
        new FakeDocument(),
        {MediaSource: null}
    );
    const status = descendants(view.element).find((node) => node.attributes.role === 'status');

    assert.equal(status.children[0].value, 'This browser does not provide MediaSource video playback.');
    view.destroy();
});

class FakeSourceBuffer {
    constructor() {
        this.listeners = {};
        this.appended = [];
        this.updating = false;
        this.seconds = 0;
        this.buffered = {
            get length() { return this.owner.seconds > 0 ? 1 : 0; },
            start: () => 0,
            end: () => this.seconds,
            owner: this,
        };
    }

    addEventListener(name, callback) { this.listeners[name] = callback; }
    appendBuffer(data) {
        this.updating = true;
        this.appended.push([...new Uint8Array(data)]);
        this.seconds += .6;
        setImmediate(() => {
            this.updating = false;
            this.listeners.updateend?.();
        });
    }
    remove() {
        this.updating = true;
        setImmediate(() => {
            this.updating = false;
            this.listeners.updateend?.();
        });
    }
}

class FakeMediaSource {
    static supported = true;
    static isTypeSupported() { return FakeMediaSource.supported; }
    constructor() {
        this.listeners = {};
        this.readyState = 'closed';
        this.buffers = [];
        FakeMediaSource.instances.push(this);
    }
    addEventListener(name, callback) { this.listeners[name] = callback; }
    removeEventListener(name, callback) {
        if (this.listeners[name] === callback) delete this.listeners[name];
    }
    open() {
        this.readyState = 'open';
        this.listeners.sourceopen?.();
    }
    addSourceBuffer(mime) {
        this.mime = mime;
        const buffer = new FakeSourceBuffer();
        this.buffers.push(buffer);
        return buffer;
    }
    endOfStream() { this.readyState = 'ended'; }
}
FakeMediaSource.instances = [];

class FakeWebSocket {
    constructor(url) {
        this.url = url;
        this.listeners = {};
        this.sent = [];
        this.closed = false;
        FakeWebSocket.instances.push(this);
    }
    addEventListener(name, callback) { this.listeners[name] = callback; }
    send(value) { this.sent.push(value); }
    close() {
        this.closed = true;
        this.listeners.close?.({});
    }
    open() { this.listeners.open?.({}); }
    receive(data) { this.listeners.message?.({data}); }
}
FakeWebSocket.instances = [];

const relayEnvironment = (fetchImpl) => ({
    MediaSource: FakeMediaSource,
    WebSocket: FakeWebSocket,
    fetch: fetchImpl,
    location: {href: 'https://zero.home:50443/addon/cam/', protocol: 'https:', host: 'zero.home:50443'},
    URL: {
        createObjectURL(source) {
            setImmediate(() => source.open());
            return 'blob:relay';
        },
        revokeObjectURL(value) { this.revoked = value; },
    },
    addEventListener() {},
    removeEventListener() {},
});

test('live page prepares then consumes ordered go2rtc MSE WebSocket fragments', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    FakeMediaSource.instances = [];
    FakeWebSocket.instances = [];
    const requests = [];
    const environment = relayEnvironment(async (url) => {
        requests.push(url);
        return {ok: true, async json() {
            return {ok: true, stream: 'alina-balcon', token: 'signed.token', expiresAt: 1030};
        }};
    });
    const documentRef = new FakeDocument();
    const view = renderLiveForm({
        cameraId: 'balcon', prepareEndpoint: '/live-stream.php', relaySocketPath: '/cam-stream/api/ws', autoStart: true,
    }, documentRef, environment);

    await flushMedia();
    await flushMedia();
    assert.deepEqual(requests, ['/live-stream.php?camera=balcon&action=prepare']);
    assert.equal(FakeWebSocket.instances.length, 1);
    const socket = FakeWebSocket.instances[0];
    assert.equal(socket.url,
        'wss://zero.home:50443/cam-stream/api/ws?camera=balcon&src=alina-balcon&token=signed.token');
    socket.open();
    assert.deepEqual(JSON.parse(socket.sent[0]), {
        type: 'mse',
        value: 'avc1.640029,avc1.64002A,avc1.640033,hvc1.1.6.L153.B0,mp4a.40.2,mp4a.40.5,flac,opus',
    });
    socket.receive(JSON.stringify({type: 'mse', value: 'video/mp4; codecs="hvc1.1.6.L153.B0,flac"'}));
    socket.receive(Uint8Array.from([1]).buffer);
    socket.receive(Uint8Array.from([2]).buffer);
    await flushMedia();
    await flushMedia();

    const buffer = FakeMediaSource.instances[0].buffers[0];
    assert.deepEqual(buffer.appended, [[1], [2]]);
    const nodes = descendants(view.element);
    const video = nodes.find(node => node.type === 'video');
    assert.equal(video.playCalls, 1);
    assert.match(nodes.find(node => node.className === 'live-status').children[0].value, /shared relay/i);
    assert.equal(requests.length, 1, 'no long-running HTTP media fetch is made');

    nodes.find(node => node.type === 'button' && node.children[0]?.value === 'Stop').listeners.click();
    assert.equal(socket.closed, true);
    assert.equal(environment.URL.revoked, 'blob:relay');
    view.destroy();
});

test('live relay rejects unsupported MSE responses but stays connected in a background tab', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    FakeMediaSource.instances = [];
    FakeWebSocket.instances = [];
    const environment = relayEnvironment(async () => ({ok: true, async json() {
        return {ok: true, stream: 'alina-door', token: 'door-token', expiresAt: 1030};
    }}));
    const documentRef = new FakeDocument();
    const view = renderLiveForm({cameraId: 'door', prepareEndpoint: '/live-stream.php', relaySocketPath: '/cam-stream/api/ws', autoStart: true},
        documentRef, environment);
    await flushMedia();
    await flushMedia();
    const socket = FakeWebSocket.instances[0];
    socket.open();
    FakeMediaSource.supported = false;
    socket.receive(JSON.stringify({type: 'mse', value: 'video/mp4; codecs="unsupported"'}));
    await flushMedia();
    assert.match(descendants(view.element).find(node => node.className === 'live-status').children[0].value, /not supported/i);
    assert.equal(socket.closed, true);

    FakeMediaSource.supported = true;
    descendants(view.element).find(node => node.type === 'button' && node.children[0]?.value === 'Start live video').listeners.click();
    await flushMedia();
    await flushMedia();
    const second = FakeWebSocket.instances.at(-1);
    documentRef.hidden = true;
    documentRef.listeners.visibilitychange();
    assert.equal(second.closed, false);
    view.destroy();
    assert.equal(second.closed, true);
});

const ptzCapabilities = {
    available: true,
    directions: ['up', 'up-right', 'right', 'down-right', 'down', 'down-left', 'left', 'up-left'],
    zoom: true,
    speeds: ['low', 'medium', 'high'],
    defaultSpeed: 'medium',
    pulseDurationMs: 180,
    presets: true,
    presetMinimum: 1,
    presetMaximum: 255,
};

const flushPromises = async () => { await new Promise(resolve => setImmediate(resolve)); };

test('live page renders capability-gated PTZ directions zoom speed and presets', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const view = renderLiveForm({cameraId: 'balcon', endpoint: '/live-stream.php',
        loadPtz: async () => ptzCapabilities, onPtzCommand: async () => ({accepted: true})}, new FakeDocument());
    await flushPromises();
    const nodes = descendants(view.element);
    assert.equal(nodes.filter(n => n.attributes['data-ptz-direction']).length, 10);
    assert.ok(nodes.find(n => n.attributes['aria-label'] === 'PTZ speed'));
    assert.ok(nodes.find(n => n.attributes['aria-label'] === 'PTZ preset number'));
    assert.ok(nodes.find(n => n.type === 'button' && n.children[0]?.value === 'GoTo'));
    assert.ok(nodes.find(n => n.type === 'button' && n.children[0]?.value === '+'));
    assert.ok(nodes.find(n => n.type === 'button' && n.children[0]?.value === '−'));
    view.destroy();
});

test('PTZ panel orders presets joystick zoom and speed with compact preset actions', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const view = renderLiveForm({cameraId: 'balcon', endpoint: '/live-stream.php',
        loadPtz: async () => ptzCapabilities, onPtzCommand: async () => ({accepted: true})}, new FakeDocument());
    await flushPromises();
    const nodes = descendants(view.element);
    const content = nodes.find(n => n.className === 'live-ptz-content');

    assert.deepEqual(content.children.slice(0, 4).map(node => node.className),
        ['ptz-presets', 'ptz-direction-grid', 'ptz-zoom-controls', 'ptz-speed-block']);
    assert.equal(nodes.some(n => n.type === 'h3' && n.children[0]?.value === 'PTZ control'), false);
    assert.equal(nodes.some(n => n.type === 'text' && /cannot reliably list|hold a direction/i.test(n.value)), false);

    const goTo = nodes.find(n => n.type === 'button' && n.children[0]?.value === 'GoTo');
    const save = nodes.find(n => n.type === 'button' && n.children[0]?.value === '+');
    const remove = nodes.find(n => n.type === 'button' && n.children[0]?.value === '−');
    assert.equal(goTo.className, 'ptz-preset-goto');
    assert.equal(save.attributes['aria-label'], 'Save current position to preset');
    assert.equal(remove.attributes['aria-label'], 'Delete preset');
    view.destroy();
});

test('live page hides actionable PTZ controls when camera capability is absent', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const view = renderLiveForm({cameraId: 'door', endpoint: '/live-stream.php',
        loadPtz: async () => ({available: false}), onPtzCommand: async () => ({accepted: true})}, new FakeDocument());
    await flushPromises();
    const nodes = descendants(view.element);
    assert.equal(nodes.filter(n => n.attributes['data-ptz-direction']).length, 0);
    assert.ok(nodes.some(n => n.type === 'text' && /not available/i.test(n.value)));
    view.destroy();
});

test('PTZ hold serializes pulses and pointer release prevents another request', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const pending = [], commands = [];
    const view = renderLiveForm({cameraId: 'balcon', endpoint: '/live-stream.php', loadPtz: async () => ptzCapabilities,
        onPtzCommand: (command) => { commands.push(command); return new Promise(resolve => pending.push(resolve)); }}, new FakeDocument());
    await flushPromises();
    const left = descendants(view.element).find(n => n.attributes['data-ptz-direction'] === 'left');
    left.listeners.pointerdown({preventDefault() {}, pointerId: 1});
    await flushPromises();
    assert.equal(commands.length, 1);
    pending.shift()({accepted: true});
    await flushPromises();
    assert.equal(commands.length, 2);
    left.listeners.pointerup({preventDefault() {}, pointerId: 1});
    pending.shift()({accepted: true});
    await flushPromises();
    assert.equal(commands.length, 2);
    assert.deepEqual(commands[0], {operation: 'move', direction: 'left', speed: 'medium', durationMs: 180});
    view.destroy();
});

test('PTZ Stop failure disables every movement control until reload', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    let calls = 0;
    const view = renderLiveForm({cameraId: 'balcon', endpoint: '/live-stream.php', loadPtz: async () => ptzCapabilities,
        onPtzCommand: async () => { calls++; throw new Error('PTZ Stop failed. Reload PTZ controls before moving again.'); }},
    new FakeDocument());
    await flushPromises();
    const buttons = descendants(view.element).filter(n => n.attributes['data-ptz-direction']);
    buttons[0].listeners.pointerdown({preventDefault() {}, pointerId: 1});
    await flushPromises();
    assert.ok(buttons.every(button => button.disabled === true));
    buttons[1].listeners.pointerdown({preventDefault() {}, pointerId: 2});
    await flushPromises();
    assert.equal(calls, 1);
    view.destroy();
});

test('PTZ hold stops on pointer cancellation visibility loss blur and destroy', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const environment = {listeners: {}, addEventListener(name, callback) { this.listeners[name] = callback; },
        removeEventListener(name, callback) { if (this.listeners[name] === callback) delete this.listeners[name]; }};
    const documentRef = new FakeDocument();
    const pending = [];
    const view = renderLiveForm({cameraId: 'balcon', endpoint: '/live-stream.php', loadPtz: async () => ptzCapabilities,
        onPtzCommand: () => new Promise(resolve => pending.push(resolve))}, documentRef, environment);
    await flushPromises();
    const up = descendants(view.element).find(n => n.attributes['data-ptz-direction'] === 'up');
    for (const cancel of [
        () => up.listeners.pointercancel({}),
        () => { documentRef.hidden = true; documentRef.listeners.visibilitychange(); documentRef.hidden = false; },
        () => environment.listeners.blur(),
        () => view.destroy(),
    ]) {
        up.listeners.pointerdown({preventDefault() {}, pointerId: 1});
        await flushPromises();
        const before = pending.length;
        cancel();
        pending.shift()({accepted: true});
        await flushPromises();
        assert.equal(pending.length, before - 1);
    }
});

test('PTZ preset save and delete require confirmation but recall does not', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const commands = [], confirmations = [];
    const environment = {confirm(message) { confirmations.push(message); return confirmations.length > 1; }};
    const view = renderLiveForm({cameraId: 'balcon', endpoint: '/live-stream.php', loadPtz: async () => ptzCapabilities,
        onPtzCommand: async command => { commands.push(command); return {accepted: true}; }}, new FakeDocument(), environment);
    await flushPromises();
    const nodes = descendants(view.element);
    const slot = nodes.find(n => n.attributes['aria-label'] === 'PTZ preset number');
    slot.value = '5';
    nodes.find(n => n.type === 'button' && n.children[0]?.value === 'GoTo').listeners.click();
    nodes.find(n => n.type === 'button' && n.children[0]?.value === '+').listeners.click();
    nodes.find(n => n.type === 'button' && n.children[0]?.value === '−').listeners.click();
    await flushPromises();
    assert.deepEqual(commands, [{operation: 'goto', preset: 5}, {operation: 'delete', preset: 5}]);
    assert.equal(confirmations.length, 2);
    view.destroy();
});

test('PTZ quick preset buttons select and immediately recall slots one through five', async () => {
    const {renderLiveForm} = await import(await moduleUrl('live-form.js'));
    const commands = [];
    const view = renderLiveForm({cameraId: 'balcon', endpoint: '/live-stream.php', loadPtz: async () => ptzCapabilities,
        onPtzCommand: async command => { commands.push(command); return {accepted: true}; }}, new FakeDocument());
    await flushPromises();
    const nodes = descendants(view.element);
    const slot = nodes.find(n => n.attributes['aria-label'] === 'PTZ preset number');
    const quick = nodes.filter(n => n.attributes['data-ptz-quick-preset']);
    assert.deepEqual(quick.map(button => button.children[0]?.value), ['1', '2', '3', '4', '5']);

    quick[3].listeners.click();
    await flushPromises();

    assert.equal(slot.value, '4');
    assert.deepEqual(commands, [{operation: 'goto', preset: 4}]);
    view.destroy();
});

test('a connected camera lands on the Live section', async () => {
    const {defaultConnectedSection} = await import(await moduleUrl('state.js'));
    assert.equal(defaultConnectedSection?.(), 'live');
});

test('exposure timing inputs follow Automatic and Manual selection', async () => {
    const {renderServiceForm} = await import(await moduleUrl('service-form.js'));
    const model = {id: 'image_basic', branch: 'Camera.Param', fields: {
        exposureMode: {label: 'Exposure mode', type: 'integer', options: [0, 1]},
        exposureMinimumUs: {label: 'Minimum', type: 'integer', disableWhen: 'exposureMode'},
    }, values: {exposureMode: 0, exposureMinimumUs: 256}};
    const root = renderServiceForm(model, {onReload() {}, onSave() {}}, new FakeDocument());
    const mode = descendants(root).find(n => n.attributes.name === 'exposureMode');
    const minimum = descendants(root).find(n => n.attributes.name === 'exposureMinimumUs');
    assert.equal(minimum.disabled, false);
    mode.value = '1';
    mode.listeners.change?.();
    assert.equal(minimum.disabled, true);
    mode.value = '0';
    mode.listeners.change?.();
    assert.equal(minimum.disabled, false);
});

const response = (data, ok = true, status = 200) => ({
    ok,
    status,
    async json() {
        return ok
            ? {ok: true, data, error: null}
            : {ok: false, data: null, error: data};
    },
});

function accessModelFixture() {
    return {revision: 'revision-1', note: 'Manage', users: [{name: 'admin', group: 'admin', memo: '', memoEditable: true,
        reserved: true, active: true, multilogin: true, permissions: ['Account', 'NetConfig'], createdVia: 'Unknown'}],
        groupRows: [{name: 'admin', memo: '', memoEditable: true, active: true, permissions: ['Account', 'NetConfig']}],
        permissions: ['Account', 'NetConfig'], points: [{id: 'dvrip', name: 'DVRIP', url: 'dvrip://192.0.2.1:34567/', port: 34567, portEditable: true, note: '', portReason: ''}]};
}

test('access manager requests selected camera and submits one changed user with visible password', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    let requested;
    const api = new CameraApi('/api.php', async (url) => { requested = url; return response({points: []}); });
    await api.accessPoints('door');
    assert.equal(requested, '/api.php?action=access&camera=door');
    const {renderAccessForm} = await import(await moduleUrl('access-form.js'));
    let saved;
    const tree = renderAccessForm(accessModelFixture(), new FakeDocument(),
        {onSave: async (payload) => { saved = payload; return {message: 'Saved'}; }});
    const password = descendants(tree).find(n => n.attributes['aria-label'] === 'New password for admin');
    assert.equal(password.type, 'text');
    assert.equal(password.placeholder, 'set new password');
    assert.ok(!password.readOnly);
    password.value = 'replacement';
    password.listeners.input();
    await tree.listeners.submit({preventDefault() {}});
    assert.equal(saved.users.length, 1); assert.equal(saved.users[0].password, 'replacement');
    assert.deepEqual(saved.groups, []); assert.deepEqual(saved.services, []);
    assert.equal(descendants(tree).filter(n => n.type === 'submit').length, 1);
    assert.ok(descendants(tree).every((n) => !n.innerHtmlWasSet));
});

test('All points is first and opens its own access page', async () => {
    const {renderEditableMenu} = await import(await moduleUrl('menu.js'));
    let opened = false;
    const nodes = renderEditableMenu([], new FakeDocument(), {onAccess() { opened = true; }, onSmtp() {}, onEditor() {}});
    const accessGroup = nodes[0];
    const accessButtons = descendants(accessGroup).filter((node) => node.type === 'button');
    assert.equal(accessGroup.children[0].children[0].value, 'ACCESS');
    assert.equal(accessButtons[0].dataset.sectionId, 'access');
    assert.equal(accessButtons[1].dataset.sectionId, 'live');
    accessButtons[0].listeners.click();
    assert.equal(opened, true);
});

test('access manager Set empty disables password input and blank fields otherwise preserve passwords', async () => {
    const {renderAccessForm, createAccessDraft, accessChanges} = await import(await moduleUrl('access-form.js'));
    const model = accessModelFixture(); const draft = createAccessDraft(model);
    assert.deepEqual(accessChanges(draft).users, []);
    const tree = renderAccessForm(model, new FakeDocument());
    const empty = descendants(tree).find(n => n.attributes['aria-label'] === 'Set empty password for admin');
    const password = descendants(tree).find(n => n.attributes['aria-label'] === 'New password for admin');
    empty.checked = true; empty.listeners.change(); assert.equal(password.disabled, true);
    empty.checked = false; empty.listeners.change(); assert.equal(password.disabled, false);
    draft.users[0].setEmpty = true;
    assert.equal(accessChanges(draft).users[0].setEmpty, true);
});

test('access manager partial save rebases successful rows while retaining pending edits', async () => {
    const {createAccessDraft, accessChanges, rebaseAccessDraft} = await import(await moduleUrl('access-form.js'));
    const model = accessModelFixture(), draft = createAccessDraft(model);
    draft.groups[0].memo = 'Saved'; draft.users[0].password = 'Pending'; draft.services[0].port = '34569';
    const fresh = structuredClone(model); fresh.revision = 'revision-2'; fresh.groupRows[0].memo = 'Saved';
    const rebased = rebaseAccessDraft(fresh, draft, [{kind: 'group', key: 'groups:admin'}]);
    const changes = accessChanges(rebased);
    assert.equal(changes.revision, 'revision-2'); assert.deepEqual(changes.groups, []);
    assert.equal(changes.users[0].password, 'Pending'); assert.deepEqual(changes.services, [{id: 'dvrip', port: 34569}]);
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

test('camera API reads PTZ capability and posts one command payload', async () => {
    const {CameraApi} = await import(await moduleUrl('api.js'));
    const requests = [];
    const api = new CameraApi('/api.php', async (url, options) => {
        requests.push([url, options]);
        return response({accepted: true});
    });
    await api.ptzCapabilities('balcon');
    await api.ptzCommand('balcon', {operation: 'goto', preset: 2});
    assert.equal(requests[0][0], '/api.php?action=ptz&camera=balcon');
    assert.equal(requests[1][0], '/api.php?action=ptz-command&camera=balcon');
    assert.equal(requests[1][1].method, 'POST');
    assert.equal(requests[1][1].body, JSON.stringify({operation: 'goto', preset: 2}));
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

test('the active section expands its collapsed menu group', async () => {
    const {markActiveSection} = await import(await moduleUrl('state.js'));
    const group = {open: false};
    const button = {
        dataset: {sectionId: 'live'},
        classList: {toggle() {}},
        setAttribute() {},
        removeAttribute() {},
        closest(selector) { return selector === 'details' ? group : null; },
    };

    markActiveSection([button], 'live');

    assert.equal(group.open, true);
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

    assert.deepEqual(nodes.map((node) => [node.type, node.children[0]?.children[0]?.value ?? '']), [
        ['details', 'ACCESS'],
        ['details', 'NETWORK'],
        ['details', 'DETECTION'],
        ['details', 'SERVICES 3rd PART'],
    ]);
    assert.deepEqual(nodes.flatMap(descendants).filter((node) => node.type === 'button').map((node) => node.dataset.sectionId), [
        'access', 'live', 'editor:wired_network', 'editor:wifi', 'editor:human_detection', 'smtp', 'editor:ftp',
    ]);
});

test('editable menu keeps newly discovered forms reachable', async () => {
    const {renderEditableMenu} = await import(await moduleUrl('menu.js'));
    const nodes = renderEditableMenu([
        {id: 'future_camera_option', name: 'Future camera option'},
    ], new FakeDocument(), {onSmtp() {}, onEditor() {}});
    const buttons = nodes.flatMap(descendants).filter((node) => node.type === 'button');
    assert.deepEqual(buttons.map((button) => button.dataset.sectionId), [
        'access', 'live', 'smtp', 'editor:future_camera_option',
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

test('expanded device details have an opaque layer above the settings panel', async () => {
    const css = await readFile(new URL('../css/app.css', import.meta.url), 'utf8');
    assert.match(css, /\.sidebar\s*\{[^}]*z-index:\s*[1-9]/s);
    assert.match(css, /\.identity-panel\s+#camera-identity\s*>\s*\.tree-list\s*>\s*dd\s*>\s*\.tree-branch\[open\]\s*\{[^}]*z-index:\s*[1-9][^}]*background:/s);
    assert.match(css, /\.identity-panel\s+\.tree-list\s+dd\s*\{[^}]*overflow-wrap:\s*anywhere/s);
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
