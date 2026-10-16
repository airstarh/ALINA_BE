import {createTalkClient} from './talk-client.js?v=20261016-4';

const text = (documentRef, value) => documentRef.createTextNode(value);

const MSE_CODECS = [
    'avc1.640029',
    'avc1.64002A',
    'avc1.640033',
    'hvc1.1.6.L153.B0',
    'mp4a.40.2',
    'mp4a.40.5',
    'flac',
    'opus',
];

export const mseCodecList = (isTypeSupported) => MSE_CODECS
    .filter(codec => isTypeSupported(`video/mp4; codecs="${codec}"`))
    .join();

export const relayWebSocketUrl = (path, {camera, stream, token}, locationRef = globalThis.location) => {
    const base = locationRef?.href || `${locationRef?.protocol || 'http:'}//${locationRef?.host || 'localhost'}/`;
    const url = new URL(path, base);
    url.protocol = url.protocol === 'https:' ? 'wss:' : 'ws:';
    url.searchParams.set('camera', camera);
    url.searchParams.set('src', stream);
    url.searchParams.set('token', token);
    return url.toString();
};

export const liveEdgeCorrection = (currentTime, liveEdge, bufferedStart) =>
    liveEdge - currentTime > 1.5 ? Math.max(bufferedStart, liveEdge - .3) : null;

export const hasStartupBuffer = (bufferedStart, liveEdge) => liveEdge - bufferedStart >= 1;

const waitForEvent = (target, name, signal) => new Promise((resolve, reject) => {
    const clear = () => {
        target.removeEventListener(name, success);
        target.removeEventListener('error', failure);
        signal.removeEventListener('abort', aborted);
    };
    const success = () => { clear(); resolve(); };
    const failure = () => { clear(); reject(new Error('The browser could not decode the camera video.')); };
    const aborted = () => { clear(); reject(new DOMException('Stopped', 'AbortError')); };
    target.addEventListener(name, success, {once: true});
    target.addEventListener('error', failure, {once: true});
    signal.addEventListener('abort', aborted, {once: true});
    if (signal.aborted) aborted();
});

export const renderLiveForm = (
    {cameraId, endpoint = null, prepareEndpoint = endpoint, relaySocketPath = '/cam-stream/api/ws',
        recordEndpoint = null, snapshotEndpoint = null,
        autoStart = false, loadPtz = null, onPtzCommand = null,
        talkback = false, requestTalkToken = null, talkClientFactory = createTalkClient,
        compact = false, onGoLive = null},
    documentRef = document,
    environment = globalThis,
) => {
    const root = documentRef.createElement('section');
    root.className = compact ? 'live-view live-view-compact' : 'live-view';

    const intro = documentRef.createElement('p');
    intro.className = 'form-note';
    intro.append(text(documentRef, 'Camera RTSP is shared by go2rtc and decoded by this browser through Media Source Extensions. No video re-encoding or camera setting changes.'));
    if (!compact) root.append(intro);

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
    if (!compact) root.append(controls);

    const status = documentRef.createElement('p');
    status.className = 'live-status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    status.textContent = 'Press Start live video.';
    if (!compact) root.append(status);

    const player = documentRef.createElement('video');
    player.setAttribute('autoplay', '');
    player.setAttribute('muted', '');
    player.setAttribute('playsinline', '');
    player.setAttribute('controls', '');
    player.muted = true;
    let destroyed = false;
    const workspace = documentRef.createElement('div');
    workspace.className = 'live-workspace';
    const videoPanel = documentRef.createElement('div');
    videoPanel.className = 'live-video-panel';
    videoPanel.append(player);
    let talkClient = null;
    let recordingPoll = null;
    if ((talkback && typeof requestTalkToken === 'function') || recordEndpoint || snapshotEndpoint) {
        const mediaControls = documentRef.createElement('div');
        mediaControls.className = 'live-media-controls';
        if (talkback && typeof requestTalkToken === 'function') {
            const talkButton = documentRef.createElement('button');
            talkButton.type = 'button';
            talkButton.className = 'talk-toggle';
            talkButton.setAttribute('aria-label', 'Start camera talkback');
            talkButton.setAttribute('title', 'Start camera talkback');
            talkButton.append(text(documentRef, '🎙'));
            const talkStatus = documentRef.createElement('span');
            talkStatus.className = 'talk-status';
            talkStatus.setAttribute('role', 'status');
            talkStatus.setAttribute('aria-live', 'polite');
            talkStatus.textContent = 'Talkback off.';
            talkClient = talkClientFactory({
                requestToken: requestTalkToken,
                onState: ({state: talkState, message}) => {
                    const active = talkState === 'active';
                    talkButton.textContent = active ? '■' : '🎙';
                    talkButton.className = active ? 'talk-toggle is-active' : 'talk-toggle';
                    talkButton.disabled = talkState === 'connecting';
                    talkButton.setAttribute('aria-label', active ? 'Stop camera talkback' : 'Start camera talkback');
                    talkButton.setAttribute('title', active ? 'Stop camera talkback' : 'Start camera talkback');
                    talkStatus.textContent = message || (active ? 'Microphone live.' : 'Talkback off.');
                },
            }, environment);
            talkButton.addEventListener('click', () => talkClient.toggle());
            mediaControls.append(talkButton, talkStatus);
        }
        if (recordEndpoint) {
            const recordButton = documentRef.createElement('button');
            recordButton.type = 'button';
            recordButton.className = 'record-toggle';
            recordButton.setAttribute('aria-label', 'Start camera recording');
            recordButton.setAttribute('aria-pressed', 'false');
            recordButton.append(text(documentRef, 'RECORD'));
            const recordStatus = documentRef.createElement('span');
            recordStatus.className = 'record-status';
            recordStatus.setAttribute('role', 'status');
            recordStatus.setAttribute('aria-live', 'polite');
            recordStatus.textContent = 'Not recording.';
            let recordingActive = false;

            const applyRecording = (recording) => {
                recordingActive = recording?.active === true;
                const stopping = recording?.stopping === true;
                recordButton.className = recordingActive ? 'record-toggle is-active' : 'record-toggle';
                recordButton.setAttribute('aria-pressed', recordingActive ? 'true' : 'false');
                recordButton.setAttribute('aria-label', recordingActive ? 'Stop camera recording' : 'Start camera recording');
                recordButton.disabled = stopping;
                recordStatus.textContent = stopping
                    ? 'Stopping recording…'
                    : recordingActive
                        ? `Recording${recording.filename ? ` · ${recording.filename}` : '…'}`
                        : 'Not recording.';
            };
            const requestRecording = async (action) => {
                const query = new URLSearchParams({camera: cameraId, action});
                const response = await environment.fetch(`${recordEndpoint}?${query}`, {
                    method: action === 'record-status' ? 'GET' : 'POST',
                    cache: 'no-store',
                });
                const payload = await response.json();
                if (!response.ok || payload?.ok !== true || !payload.recording) {
                    throw new Error(payload?.error || 'Camera recording request failed.');
                }
                if (!destroyed) applyRecording(payload.recording);
                return payload.recording;
            };
            if (typeof environment.setInterval === 'function') {
                recordingPoll = environment.setInterval(() => requestRecording('record-status').catch(() => {}), 2000);
            }
            recordButton.addEventListener('click', async () => {
                recordButton.disabled = true;
                try {
                    await requestRecording(recordingActive ? 'record-stop' : 'record-start');
                } catch (error) {
                    if (!destroyed) recordStatus.textContent = error?.message || 'Camera recording request failed.';
                } finally {
                    if (!destroyed && !recordingActive) recordButton.disabled = false;
                }
            });
            mediaControls.append(recordButton, recordStatus);
            void requestRecording('record-status').catch((error) => {
                if (!destroyed) {
                    recordButton.disabled = true;
                    recordStatus.textContent = error?.message || 'Camera recording is unavailable.';
                }
            });
        }
        if (snapshotEndpoint) {
            const snapshotButton = documentRef.createElement('button');
            snapshotButton.type = 'button';
            snapshotButton.className = 'snapshot-button';
            snapshotButton.setAttribute('aria-label', 'Save camera snapshot');
            snapshotButton.append(text(documentRef, 'PrintScreen'));
            const snapshotStatus = documentRef.createElement('span');
            snapshotStatus.className = 'snapshot-status';
            snapshotStatus.setAttribute('role', 'status');
            snapshotStatus.setAttribute('aria-live', 'polite');
            snapshotButton.addEventListener('click', async () => {
                snapshotButton.disabled = true;
                snapshotStatus.textContent = 'Saving snapshot…';
                try {
                    const query = new URLSearchParams({camera: cameraId, action: 'snapshot-save'});
                    const response = await environment.fetch(`${snapshotEndpoint}?${query}`, {
                        method: 'POST',
                        cache: 'no-store',
                    });
                    const payload = await response.json();
                    if (!response.ok || payload?.ok !== true || typeof payload.snapshot?.filename !== 'string') {
                        throw new Error(payload?.error || 'Camera snapshot request failed.');
                    }
                    if (!destroyed) snapshotStatus.textContent = `Saved · ${payload.snapshot.filename}`;
                } catch (error) {
                    if (!destroyed) snapshotStatus.textContent = error?.message || 'Camera snapshot request failed.';
                } finally {
                    if (!destroyed) snapshotButton.disabled = false;
                }
            });
            mediaControls.append(snapshotButton, snapshotStatus);
        }
        if (typeof onGoLive === 'function') {
            const goLive = documentRef.createElement('button');
            goLive.type = 'button';
            goLive.className = 'go-live-button';
            goLive.append(text(documentRef, 'Go Live'));
            goLive.addEventListener('click', () => onGoLive(cameraId));
            mediaControls.append(goLive);
        }
        videoPanel.append(mediaControls);
    }
    workspace.append(videoPanel);

    const ptzPanel = documentRef.createElement('aside');
    ptzPanel.className = 'live-ptz';
    const ptzContent = documentRef.createElement('div');
    ptzContent.className = 'live-ptz-content';
    ptzContent.append(text(documentRef, loadPtz ? 'Checking camera PTZ capability…' : 'PTZ control is not available.'));
    ptzPanel.append(ptzContent);
    if (!compact) workspace.append(ptzPanel);
    root.append(workspace);

    let controller = null;
    let socket = null;
    let mediaSource = null;
    let objectUrl = null;
    let holdGeneration = 0;
    let movementLocked = false;
    let movementButtons = [];

    const cancelMovement = () => { holdGeneration++; };

    const describeError = (error) => error?.message || 'PTZ command failed.';

    const renderPtz = (capabilities) => {
        ptzContent.replaceChildren();
        if (!capabilities?.available || typeof onPtzCommand !== 'function') {
            ptzContent.append(text(documentRef, 'PTZ control is not available on this camera.'));
            return;
        }

        const ptzStatus = documentRef.createElement('p');
        ptzStatus.className = 'live-ptz-status';
        ptzStatus.setAttribute('role', 'status');
        ptzStatus.setAttribute('aria-live', 'polite');
        ptzStatus.textContent = '';

        const speedLabel = documentRef.createElement('label');
        speedLabel.append(text(documentRef, 'Speed'));
        const speed = documentRef.createElement('select');
        speed.setAttribute('aria-label', 'PTZ speed');
        for (const value of capabilities.speeds || []) {
            const option = documentRef.createElement('option');
            option.value = value;
            option.textContent = value[0].toUpperCase() + value.slice(1);
            option.selected = value === capabilities.defaultSpeed;
            speed.append(option);
        }
        speed.value = capabilities.defaultSpeed || capabilities.speeds?.[0] || 'medium';
        speedLabel.append(speed);
        const speedBlock = documentRef.createElement('div');
        speedBlock.className = 'ptz-speed-block';
        speedBlock.append(speedLabel, ptzStatus);

        const directionGrid = documentRef.createElement('div');
        directionGrid.className = 'ptz-direction-grid';
        const directionSymbols = {
            'up-left': '↖', up: '↑', 'up-right': '↗', left: '←', right: '→',
            'down-left': '↙', down: '↓', 'down-right': '↘',
        };

        const lockMovement = (message) => {
            movementLocked = true;
            cancelMovement();
            for (const button of movementButtons) button.disabled = true;
            ptzStatus.textContent = message;
        };

        const sendPulse = async (direction, generation) => {
            while (!destroyed && !movementLocked && generation === holdGeneration) {
                try {
                    await onPtzCommand({operation: 'move', direction, speed: speed.value,
                        durationMs: capabilities.pulseDurationMs});
                    if (generation === holdGeneration) ptzStatus.textContent = `Moving ${direction} · ${speed.value}`;
                } catch (error) {
                    const message = describeError(error);
                    if (/stop failed/i.test(message)) lockMovement(`${message} Movement is disabled until this page is reloaded.`);
                    else {
                        cancelMovement();
                        ptzStatus.textContent = message;
                    }
                }
            }
        };

        const makeMovementButton = (direction, caption) => {
            const button = documentRef.createElement('button');
            button.type = 'button';
            button.className = 'ptz-move-button';
            button.setAttribute('data-ptz-direction', direction);
            button.setAttribute('aria-label', `Move ${direction}`);
            button.append(text(documentRef, caption));
            const begin = (event) => {
                event?.preventDefault?.();
                if (destroyed || movementLocked) return;
                button.setPointerCapture?.(event?.pointerId);
                const generation = ++holdGeneration;
                void sendPulse(direction, generation);
            };
            const end = (event) => { event?.preventDefault?.(); cancelMovement(); };
            button.addEventListener('pointerdown', begin);
            button.addEventListener('pointerup', end);
            button.addEventListener('pointercancel', end);
            button.addEventListener('lostpointercapture', end);
            button.addEventListener('keydown', (event) => {
                if ((event.key === ' ' || event.key === 'Enter') && !event.repeat) begin(event);
            });
            button.addEventListener('keyup', (event) => {
                if (event.key === ' ' || event.key === 'Enter') end(event);
            });
            movementButtons.push(button);
            return button;
        };

        for (const direction of ['up-left', 'up', 'up-right', 'left']) {
            directionGrid.append(makeMovementButton(direction, directionSymbols[direction]));
        }
        const stopMovement = documentRef.createElement('button');
        stopMovement.type = 'button';
        stopMovement.className = 'ptz-stop-button';
        stopMovement.append(text(documentRef, '■'));
        stopMovement.setAttribute('aria-label', 'Stop repeated PTZ movement');
        stopMovement.addEventListener('click', () => {
            cancelMovement();
            ptzStatus.textContent = 'Movement stopped.';
        });
        directionGrid.append(stopMovement);
        for (const direction of ['right', 'down-left', 'down', 'down-right']) {
            directionGrid.append(makeMovementButton(direction, directionSymbols[direction]));
        }

        const zoom = documentRef.createElement('div');
        zoom.className = 'ptz-zoom-controls';
        if (capabilities.zoom) {
            zoom.append(makeMovementButton('zoom-in', 'Zoom +'), makeMovementButton('zoom-out', 'Zoom −'));
        }

        if (capabilities.presets) {
            const presets = documentRef.createElement('div');
            presets.className = 'ptz-presets';
            const presetField = documentRef.createElement('div');
            presetField.className = 'ptz-preset-field';
            const presetLabel = documentRef.createElement('label');
            presetLabel.setAttribute('for', 'ptz-preset-number');
            presetLabel.append(text(documentRef, 'Preset slot'));
            const preset = documentRef.createElement('input');
            preset.type = 'number';
            preset.id = 'ptz-preset-number';
            preset.min = String(capabilities.presetMinimum || 1);
            preset.max = String(capabilities.presetMaximum || 255);
            preset.value = String(capabilities.presetMinimum || 1);
            preset.setAttribute('aria-label', 'PTZ preset number');
            const presetButtons = documentRef.createElement('div');
            presetButtons.className = 'ptz-preset-buttons';
            const runPresetAction = async (operation, confirmation = '') => {
                const number = Number(preset.value);
                if (!Number.isInteger(number) || number < Number(preset.min) || number > Number(preset.max)) {
                    ptzStatus.textContent = `Enter a preset number from ${preset.min} to ${preset.max}.`;
                    return;
                }
                if (confirmation && (!environment.confirm || !environment.confirm(`${confirmation} preset ${number}?`))) return;
                try {
                    await onPtzCommand({operation, preset: number});
                    ptzStatus.textContent = operation === 'goto'
                        ? `Moving to preset ${number}.`
                        : operation === 'save' ? `Preset ${number} saved.` : `Preset ${number} deleted.`;
                } catch (error) {
                    ptzStatus.textContent = describeError(error);
                }
            };
            const presetAction = (caption, operation, confirmation = '', ariaLabel = '', className = '') => {
                const button = documentRef.createElement('button');
                button.type = 'button';
                button.className = className;
                if (ariaLabel) {
                    button.setAttribute('aria-label', ariaLabel);
                    button.setAttribute('title', ariaLabel);
                }
                button.append(text(documentRef, caption));
                button.addEventListener('click', () => runPresetAction(operation, confirmation));
                return button;
            };
            const quickPresets = documentRef.createElement('div');
            quickPresets.className = 'ptz-quick-presets';
            for (let number = 1; number <= 5; number++) {
                if (number < Number(preset.min) || number > Number(preset.max)) continue;
                const quick = documentRef.createElement('button');
                quick.type = 'button';
                quick.setAttribute('data-ptz-quick-preset', String(number));
                quick.setAttribute('aria-label', `Go to preset ${number}`);
                quick.append(text(documentRef, String(number)));
                quick.addEventListener('click', async () => {
                    preset.value = String(number);
                    await runPresetAction('goto');
                });
                quickPresets.append(quick);
            }
            presetButtons.append(
                presetAction('GoTo', 'goto', '', 'Go to preset', 'ptz-preset-goto'),
                presetAction('+', 'save', 'Overwrite', 'Save current position to preset'),
                presetAction('−', 'delete', 'Delete', 'Delete preset'),
            );
            presetField.append(presetLabel, quickPresets, preset);
            presets.append(presetField, presetButtons);
            ptzContent.append(presets);
        }

        ptzContent.append(directionGrid, zoom, speedBlock);
    };

    const cleanup = (message = '') => {
        const activeController = controller;
        controller = null;
        activeController?.abort();
        const activeSocket = socket;
        socket = null;
        activeSocket?.close?.();
        if (mediaSource?.readyState === 'open') {
            try { mediaSource.endOfStream(); } catch {}
        }
        mediaSource = null;
        player.pause?.();
        player.removeAttribute?.('src');
        player.load?.();
        if (objectUrl) environment.URL?.revokeObjectURL?.(objectUrl);
        objectUrl = null;
        start.disabled = destroyed;
        stop.disabled = true;
        if (message && !destroyed) status.textContent = message;
    };

    const startPlayback = async () => {
        cleanup();
        if (!environment.MediaSource || !environment.WebSocket) {
            status.textContent = 'This browser does not provide MediaSource video playback.';
            return;
        }
        const current = new AbortController();
        controller = current;
        start.disabled = true;
        stop.disabled = false;
        const query = `camera=${encodeURIComponent(cameraId)}`;
        try {
            status.textContent = 'Preparing shared camera stream…';
            const response = await environment.fetch(`${prepareEndpoint}?${query}&action=prepare`, {
                signal: current.signal,
                cache: 'no-store',
            });
            const prepared = await response.json();
            if (!response.ok || !prepared.ok) {
                throw new Error(prepared.error || 'Could not prepare the shared camera stream.');
            }
            if (typeof prepared.stream !== 'string' || typeof prepared.token !== 'string') {
                throw new Error('Camera relay returned invalid authorization data.');
            }

            const codecs = mseCodecList(environment.MediaSource.isTypeSupported);
            if (!codecs) throw new Error('This browser does not support the camera relay codecs.');

            const source = new environment.MediaSource();
            mediaSource = source;
            const opened = waitForEvent(source, 'sourceopen', current.signal);
            objectUrl = environment.URL.createObjectURL(source);
            player.src = objectUrl;
            await opened;

            const currentSocket = new environment.WebSocket(relayWebSocketUrl(relaySocketPath, {
                camera: cameraId,
                stream: prepared.stream,
                token: prepared.token,
            }, environment.location));
            socket = currentSocket;
            currentSocket.binaryType = 'arraybuffer';
            const pending = [];
            let buffer = null;
            let playbackStarted = false;

            const fail = (message) => {
                if (controller === current && !current.signal.aborted) cleanup(message);
            };
            const pump = () => {
                if (!buffer || buffer.updating || pending.length === 0 || current.signal.aborted) return;
                try {
                    buffer.appendBuffer(pending.shift());
                } catch {
                    fail('The browser could not decode the camera video.');
                }
            };
            const updatePlayback = () => {
                if (!buffer?.buffered?.length) {
                    pump();
                    return;
                }
                const bufferedStart = buffer.buffered.start(0);
                const end = buffer.buffered.end(buffer.buffered.length - 1);
                if (!playbackStarted && hasStartupBuffer(bufferedStart, end)) {
                    playbackStarted = true;
                    Promise.resolve(player.play()).catch(() => fail('The browser could not start camera playback.'));
                } else if (playbackStarted) {
                    const correction = liveEdgeCorrection(player.currentTime, end, bufferedStart);
                    if (correction !== null) player.currentTime = correction;
                }
                if (player.currentTime > 15 && bufferedStart < player.currentTime - 10 && !buffer.updating) {
                    buffer.remove(0, player.currentTime - 10);
                    return;
                }
                pump();
            };

            currentSocket.addEventListener('open', () => {
                if (!current.signal.aborted) currentSocket.send(JSON.stringify({type: 'mse', value: codecs}));
            });
            currentSocket.addEventListener('message', (event) => {
                if (current.signal.aborted) return;
                if (typeof event.data !== 'string') {
                    pending.push(event.data);
                    pump();
                    return;
                }
                let message;
                try {
                    message = JSON.parse(event.data);
                } catch {
                    fail('Camera relay returned an invalid response.');
                    return;
                }
                if (message.type === 'error') {
                    fail(typeof message.value === 'string' ? message.value : 'Camera relay failed.');
                    return;
                }
                if (message.type !== 'mse') return;
                if (typeof message.value !== 'string' || !environment.MediaSource.isTypeSupported(message.value)) {
                    fail('The camera stream format is not supported by this browser.');
                    return;
                }
                try {
                    buffer = source.addSourceBuffer(message.value);
                    buffer.mode = 'segments';
                    buffer.addEventListener('updateend', updatePlayback);
                    const videoCodec = message.value.includes('hvc1') ? 'HEVC' : message.value.includes('avc1') ? 'H264' : 'video';
                    const hasAudio = /mp4a|flac|opus/.test(message.value);
                    status.textContent = `${videoCodec}${hasAudio ? ' · camera audio available' : ''} · shared relay · native browser playback`;
                    pump();
                } catch {
                    fail('The camera stream format is not supported by this browser.');
                }
            });
            currentSocket.addEventListener('error', () => fail('Camera relay connection failed.'));
            currentSocket.addEventListener('close', () => {
                if (socket === currentSocket) socket = null;
                if (controller === current && !current.signal.aborted) {
                    cleanup('Live video ended. Press Start to reconnect.');
                }
            });
        } catch (error) {
            if (error?.name !== 'AbortError' && controller === current) cleanup(error.message);
        }
    };

    start.disabled = false;
    start.addEventListener('click', startPlayback);
    stop.addEventListener('click', () => cleanup('Stopped.'));
    const visibility = () => {
        if (documentRef.hidden) cancelMovement();
    };
    documentRef.addEventListener?.('visibilitychange', visibility);
    const blur = () => cancelMovement();
    environment.addEventListener?.('blur', blur);
    if (loadPtz) {
        Promise.resolve().then(loadPtz).then((capabilities) => {
            if (!destroyed) renderPtz(capabilities);
        }).catch((error) => {
            if (!destroyed) {
                ptzContent.replaceChildren(text(documentRef, `PTZ controls could not be loaded: ${describeError(error)}`));
            }
        });
    }
    if (autoStart) startPlayback();

    return {
        element: root,
        destroy() {
            destroyed = true;
            cancelMovement();
            talkClient?.destroy();
            if (recordingPoll !== null) environment.clearInterval?.(recordingPoll);
            cleanup();
            documentRef.removeEventListener?.('visibilitychange', visibility);
            environment.removeEventListener?.('blur', blur);
        },
    };
};
