import {createTalkClient} from './talk-client.js?v=20261016-5';

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
        compact = false, onGoLive = null, onStatus = () => {}},
    documentRef = document,
    environment = globalThis,
) => {
    const root = documentRef.createElement('section');
    root.className = compact ? 'live-view live-view-compact' : 'live-view';
    const reportStatus = (key, label, message, kind = 'neutral') => onStatus({
        cameraId,
        key,
        label,
        message,
        kind,
    });
    const setLiveStatus = (message, kind = 'neutral') => reportStatus('live', 'Live', message, kind);
    const setTalkStatus = (message, kind = 'neutral') => reportStatus('microphone', 'Microphone', message, kind);
    const setRecordStatus = (message, kind = 'neutral') => reportStatus('record', 'RECORD', message, kind);
    const setSnapshotStatus = (message, kind = 'neutral') => reportStatus('snapshot', 'PrintScreen', message, kind);
    const setPtzStatus = (message, kind = 'neutral') => reportStatus('ptz', 'PTZ', message, kind);

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

    setLiveStatus('Press Start live video.');

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
            setTalkStatus('Talkback off.');
            talkClient = talkClientFactory({
                requestToken: requestTalkToken,
                onState: ({state: talkState, message}) => {
                    const active = talkState === 'active';
                    talkButton.textContent = active ? '■' : '🎙';
                    talkButton.className = active ? 'talk-toggle is-active' : 'talk-toggle';
                    talkButton.disabled = talkState === 'connecting';
                    talkButton.setAttribute('aria-label', active ? 'Stop camera talkback' : 'Start camera talkback');
                    talkButton.setAttribute('title', active ? 'Stop camera talkback' : 'Start camera talkback');
                    setTalkStatus(active ? 'ON AIR' : (message || 'Talkback off.'), active ? 'ok' : 'neutral');
                },
            }, environment);
            talkButton.addEventListener('click', () => talkClient.toggle());
            mediaControls.append(talkButton);
        }
        if (recordEndpoint) {
            const recordButton = documentRef.createElement('button');
            recordButton.type = 'button';
            recordButton.className = 'record-toggle';
            recordButton.setAttribute('aria-label', 'Start camera recording');
            recordButton.setAttribute('aria-pressed', 'false');
            recordButton.setAttribute('title', 'Start camera recording');
            recordButton.append(text(documentRef, '⏺'));
            setRecordStatus('Not recording.');
            let recordingActive = false;

            const applyRecording = (recording) => {
                recordingActive = recording?.active === true;
                const stopping = recording?.stopping === true;
                recordButton.className = recordingActive ? 'record-toggle is-active' : 'record-toggle';
                recordButton.setAttribute('aria-pressed', recordingActive ? 'true' : 'false');
                recordButton.setAttribute('aria-label', recordingActive ? 'Stop camera recording' : 'Start camera recording');
                recordButton.setAttribute('title', recordingActive ? 'Stop camera recording' : 'Start camera recording');
                recordButton.disabled = stopping;
                const message = stopping
                    ? 'Stopping recording…'
                    : recordingActive
                        ? `Recording${recording.filename ? ` · ${recording.filename}` : '…'}`
                        : 'Not recording.';
                setRecordStatus(message, recordingActive ? 'recording' : 'neutral');
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
                    if (!destroyed) setRecordStatus(error?.message || 'Camera recording request failed.', 'error');
                } finally {
                    if (!destroyed && !recordingActive) recordButton.disabled = false;
                }
            });
            mediaControls.append(recordButton);
            void requestRecording('record-status').catch((error) => {
                if (!destroyed) {
                    recordButton.disabled = true;
                    setRecordStatus(error?.message || 'Camera recording is unavailable.', 'error');
                }
            });
        }
        if (snapshotEndpoint) {
            const snapshotButton = documentRef.createElement('button');
            snapshotButton.type = 'button';
            snapshotButton.className = 'snapshot-button';
            snapshotButton.setAttribute('aria-label', 'Save camera snapshot');
            snapshotButton.setAttribute('title', 'Save camera snapshot');
            snapshotButton.append(text(documentRef, '📷'));
            snapshotButton.addEventListener('click', async () => {
                snapshotButton.disabled = true;
                setSnapshotStatus('Saving snapshot…');
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
                    if (!destroyed) setSnapshotStatus(`Saved · ${payload.snapshot.filename}`, 'ok');
                } catch (error) {
                    if (!destroyed) setSnapshotStatus(error?.message || 'Camera snapshot request failed.', 'error');
                } finally {
                    if (!destroyed) snapshotButton.disabled = false;
                }
            });
            mediaControls.append(snapshotButton);
        }
        if (typeof onGoLive === 'function') {
            const goLive = documentRef.createElement('button');
            goLive.type = 'button';
            goLive.className = 'go-live-button';
            goLive.setAttribute('aria-label', 'Open camera live view');
            goLive.setAttribute('title', 'Open camera live view');
            goLive.append(text(documentRef, '🖥️'));
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
        speedBlock.append(speedLabel);

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
            setPtzStatus(message, 'error');
        };

        const sendPulse = async (direction, generation) => {
            while (!destroyed && !movementLocked && generation === holdGeneration) {
                try {
                    await onPtzCommand({operation: 'move', direction, speed: speed.value,
                        durationMs: capabilities.pulseDurationMs});
                    if (generation === holdGeneration) setPtzStatus(`Moving ${direction} · ${speed.value}`);
                } catch (error) {
                    const message = describeError(error);
                    if (/stop failed/i.test(message)) lockMovement(`${message} Movement is disabled until this page is reloaded.`);
                    else {
                        cancelMovement();
                        setPtzStatus(message, 'error');
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
            setPtzStatus('Movement stopped.');
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
                    setPtzStatus(`Enter a preset number from ${preset.min} to ${preset.max}.`, 'error');
                    return;
                }
                if (confirmation && (!environment.confirm || !environment.confirm(`${confirmation} preset ${number}?`))) return;
                try {
                    await onPtzCommand({operation, preset: number});
                    setPtzStatus(operation === 'goto'
                        ? `Moving to preset ${number}.`
                        : operation === 'save' ? `Preset ${number} saved.` : `Preset ${number} deleted.`, 'ok');
                } catch (error) {
                    setPtzStatus(describeError(error), 'error');
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
        if (message && !destroyed) setLiveStatus(message);
    };

    const startPlayback = async () => {
        cleanup();
        if (!environment.MediaSource || !environment.WebSocket) {
            setLiveStatus('This browser does not provide MediaSource video playback.', 'error');
            return;
        }
        const current = new AbortController();
        controller = current;
        start.disabled = true;
        stop.disabled = false;
        const query = `camera=${encodeURIComponent(cameraId)}`;
        try {
            setLiveStatus('Preparing shared camera stream…');
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
                    setLiveStatus(`${videoCodec}${hasAudio ? ' · camera audio available' : ''} · shared relay · native browser playback`, 'ok');
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
                setPtzStatus(`PTZ controls could not be loaded: ${describeError(error)}`, 'error');
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
