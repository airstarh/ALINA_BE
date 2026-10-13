const text = (documentRef, value) => documentRef.createTextNode(value);

const mimeCandidates = (video) => {
    const codec = video.codec_name;
    const level = Number(video.level) || (codec === 'hevc' ? 150 : 40);
    if (codec === 'hevc') return [`video/mp4; codecs="hvc1.1.6.L${level}.B0"`, 'video/mp4; codecs="hvc1"'];
    const profile = /High/.test(video.profile) ? '64' : /Main/.test(video.profile) ? '4D' : '42';
    return [`video/mp4; codecs="avc1.${profile}00${level.toString(16).padStart(2, '0')}"`, 'video/mp4; codecs="avc1"'];
};

const waitForEvent = (target, name, signal) => new Promise((resolve, reject) => {
    const clear = () => {
        target.removeEventListener(name, success);
        target.removeEventListener('error', failure);
        signal.removeEventListener('abort', aborted);
    };
    const success = () => { clear(); resolve(); };
    const failure = () => { clear(); reject(new Error('The browser could not decode Balcon video.')); };
    const aborted = () => { clear(); reject(new DOMException('Stopped', 'AbortError')); };
    target.addEventListener(name, success, {once: true});
    target.addEventListener('error', failure, {once: true});
    signal.addEventListener('abort', aborted, {once: true});
    if (signal.aborted) aborted();
});

export const renderLiveForm = (
    {cameraId, endpoint, autoStart = false},
    documentRef = document,
    environment = globalThis,
) => {
    const root = documentRef.createElement('section');
    root.className = 'live-view';

    const intro = documentRef.createElement('p');
    intro.className = 'form-note';
    intro.append(text(documentRef, 'Balcon RTSP is copied into fragmented MP4 by FFmpeg and decoded by this browser. No re-encoding or camera setting changes.'));
    root.append(intro);

    const controls = documentRef.createElement('div');
    controls.className = 'live-controls';
    const start = documentRef.createElement('button');
    start.type = 'button';
    start.className = 'primary-button';
    start.append(text(documentRef, 'Start live video'));
    const stop = documentRef.createElement('button');
    stop.type = 'button';
    stop.append(text(documentRef, 'Stop'));
    stop.disabled = true;
    controls.append(start, stop);
    root.append(controls);

    const status = documentRef.createElement('p');
    status.className = 'live-status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    status.textContent = cameraId === 'balcon'
        ? 'Press Start live video.'
        : 'Live video is currently available for Balcon only.';
    root.append(status);

    const player = documentRef.createElement('video');
    player.setAttribute('autoplay', '');
    player.setAttribute('muted', '');
    player.setAttribute('playsinline', '');
    player.setAttribute('controls', '');
    player.muted = true;
    root.append(player);

    let controller = null;
    let objectUrl = null;
    let destroyed = false;

    const cleanup = (message = '') => {
        controller?.abort();
        controller = null;
        player.pause?.();
        player.removeAttribute?.('src');
        player.load?.();
        if (objectUrl) environment.URL?.revokeObjectURL?.(objectUrl);
        objectUrl = null;
        start.disabled = cameraId !== 'balcon' || destroyed;
        stop.disabled = true;
        if (message && !destroyed) status.textContent = message;
    };

    const startPlayback = async () => {
        cleanup();
        if (!environment.MediaSource) {
            status.textContent = 'This browser does not provide MediaSource video playback.';
            return;
        }
        const current = new AbortController();
        controller = current;
        start.disabled = true;
        stop.disabled = false;
        const query = `camera=${encodeURIComponent(cameraId)}`;
        try {
            status.textContent = 'Checking Balcon video format…';
            const response = await environment.fetch(`${endpoint}?${query}&action=info`, {
                signal: current.signal,
                cache: 'no-store',
            });
            const info = await response.json();
            if (!response.ok || !info.ok) throw new Error(info.error || 'Balcon stream probe failed.');
            const mime = mimeCandidates(info.video)
                .find((candidate) => environment.MediaSource.isTypeSupported(candidate));
            if (!mime) {
                throw new Error(`${info.video.codec_name.toUpperCase()} ${info.video.width}×${info.video.height} is not supported by this browser.`);
            }

            const source = new environment.MediaSource();
            const opened = waitForEvent(source, 'sourceopen', current.signal);
            objectUrl = environment.URL.createObjectURL(source);
            player.src = objectUrl;
            await opened;
            const buffer = source.addSourceBuffer(mime);
            buffer.mode = 'segments';
            const stream = await environment.fetch(
                `${endpoint}?${query}&action=stream&codec=${encodeURIComponent(info.video.codec_name)}`,
                {signal: current.signal, cache: 'no-store'}
            );
            if (!stream.ok) {
                const error = await stream.json();
                throw new Error(error.error || 'Could not start Balcon live video.');
            }
            status.textContent = `${info.video.codec_name.toUpperCase()} · ${info.video.width}×${info.video.height} · native browser playback`;
            const reader = stream.body.getReader();
            while (!current.signal.aborted) {
                const {done, value} = await reader.read();
                if (done) break;
                const appended = waitForEvent(buffer, 'updateend', current.signal);
                buffer.appendBuffer(value);
                await appended;
                if (buffer.buffered.length) {
                    const end = buffer.buffered.end(buffer.buffered.length - 1);
                    if (end - player.currentTime > 1.5) {
                        player.currentTime = Math.max(buffer.buffered.start(0), end - .3);
                    }
                    if (player.paused) await player.play();
                    if (player.currentTime > 15 && buffer.buffered.start(0) < player.currentTime - 10) {
                        const removed = waitForEvent(buffer, 'updateend', current.signal);
                        buffer.remove(0, player.currentTime - 10);
                        await removed;
                    }
                }
            }
            if (source.readyState === 'open') source.endOfStream();
            if (!current.signal.aborted) cleanup('Live video ended. Press Start to reconnect.');
        } catch (error) {
            if (error?.name !== 'AbortError' && controller === current) cleanup(error.message);
        }
    };

    start.disabled = cameraId !== 'balcon';
    start.addEventListener('click', startPlayback);
    stop.addEventListener('click', () => cleanup('Stopped.'));
    const visibility = () => {
        if (documentRef.hidden && controller) cleanup('Stopped because this page became hidden.');
    };
    documentRef.addEventListener?.('visibilitychange', visibility);
    if (autoStart && cameraId === 'balcon') startPlayback();

    return {
        element: root,
        destroy() {
            destroyed = true;
            cleanup();
            documentRef.removeEventListener?.('visibilitychange', visibility);
        },
    };
};
