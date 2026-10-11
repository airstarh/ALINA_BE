// Temporary probe: no dependencies, WebRTC, plugin or decoder library.
const video = document.querySelector('#video');
const status = document.querySelector('#status');
const start = document.querySelector('#start');
const stop = document.querySelector('#stop');
const camera = document.querySelector('#camera');
let controller = null;
let objectUrl = null;

function cleanup(message) {
    controller?.abort();
    controller = null;
    video.pause();
    video.removeAttribute('src');
    video.load();
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = null;
    start.disabled = false;
    stop.disabled = true;
    camera.disabled = false;
    if (message) status.textContent = message;
}

function waitForEvent(target, name, signal) {
    return new Promise((resolve, reject) => {
        const clear = () => {
            target.removeEventListener(name, ok);
            target.removeEventListener('error', fail);
            signal.removeEventListener('abort', abort);
        };
        const ok = () => { clear(); resolve(); };
        const fail = () => { clear(); reject(new Error('Browser could not decode the copied video stream.')); };
        const abort = () => { clear(); reject(new DOMException('Stopped', 'AbortError')); };
        target.addEventListener(name, ok, {once: true});
        target.addEventListener('error', fail, {once: true});
        signal.addEventListener('abort', abort, {once: true});
        if (signal.aborted) abort();
    });
}

start.addEventListener('click', async () => {
    cleanup();
    const current = new AbortController();
    controller = current;
    const signal = current.signal;
    start.disabled = true;
    stop.disabled = false;
    camera.disabled = true;
    const endpoint = './stuff/php/life-prototype-stream.php?camera=' + encodeURIComponent(camera.value);
    try {
        if (!window.MediaSource) throw new Error('This browser does not expose native MediaSource playback.');
        status.textContent = 'Checking the camera video codec…';
        const response = await fetch(endpoint + '&action=info', {signal, cache: 'no-store'});
        const info = await response.json();
        if (!response.ok || !info.ok) throw new Error(info.error || 'Camera stream probe failed.');
        const codec = info.video.codec_name;
        const level = Number(info.video.level) || (codec === 'hevc' ? 150 : 40);
        const avcProfile = /High/.test(info.video.profile) ? '64' : /Main/.test(info.video.profile) ? '4D' : '42';
        const codecs = codec === 'hevc'
            ? [`hvc1.1.6.L${level}.B0`, 'hvc1']
            : [`avc1.${avcProfile}00${level.toString(16).padStart(2, '0')}`, 'avc1'];
        const mime = codecs.map(c => `video/mp4; codecs="${c}"`).find(m => MediaSource.isTypeSupported(m));
        if (!mime) throw new Error(`Camera sends ${codec.toUpperCase()} (${info.video.width}×${info.video.height}); this browser cannot play it through native MSE. No camera settings were changed.`);
        const source = new MediaSource();
        const opened = waitForEvent(source, 'sourceopen', signal);
        objectUrl = URL.createObjectURL(source);
        video.src = objectUrl;
        await opened;
        const buffer = source.addSourceBuffer(mime);
        buffer.mode = 'segments';
        const stream = await fetch(endpoint + '&action=stream&codec=' + encodeURIComponent(codec), {signal, cache: 'no-store'});
        if (!stream.ok) {
            const error = await stream.json();
            throw new Error(error.error || 'Could not start the video stream.');
        }
        status.textContent = `${codec.toUpperCase()} · ${info.video.width}×${info.video.height} · native playback, no re-encoding. Limit: 90 seconds.`;
        const reader = stream.body.getReader();
        while (!signal.aborted) {
            const {done, value} = await reader.read();
            if (done) break;
            const appended = waitForEvent(buffer, 'updateend', signal);
            buffer.appendBuffer(value);
            await appended;
            if (buffer.buffered.length) {
                const end = buffer.buffered.end(buffer.buffered.length - 1);
                if (end - video.currentTime > 1.5) video.currentTime = Math.max(buffer.buffered.start(0), end - .3);
                if (video.paused) await video.play();
                if (video.currentTime > 15 && buffer.buffered.start(0) < video.currentTime - 10) {
                    const removed = waitForEvent(buffer, 'updateend', signal);
                    buffer.remove(0, video.currentTime - 10);
                    await removed;
                }
            }
        }
        if (source.readyState === 'open') source.endOfStream();
        status.textContent = 'Experiment finished (90-second limit). Press Start to reconnect.';
        start.disabled = false;
        stop.disabled = true;
        camera.disabled = false;
    } catch (error) {
        if (error.name !== 'AbortError' && controller === current) cleanup(error.message);
    }
});
stop.addEventListener('click', () => cleanup('Stopped.'));
window.addEventListener('pagehide', () => cleanup());
document.addEventListener('visibilitychange', () => {
    if (document.hidden && controller) cleanup('Paused because the page became hidden. Press Start to reconnect.');
});
