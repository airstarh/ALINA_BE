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
    const failure = () => { clear(); reject(new Error('The browser could not decode the camera video.')); };
    const aborted = () => { clear(); reject(new DOMException('Stopped', 'AbortError')); };
    target.addEventListener(name, success, {once: true});
    target.addEventListener('error', failure, {once: true});
    signal.addEventListener('abort', aborted, {once: true});
    if (signal.aborted) aborted();
});

export const renderLiveForm = (
    {cameraId, endpoint, autoStart = false, loadPtz = null, onPtzCommand = null},
    documentRef = document,
    environment = globalThis,
) => {
    const root = documentRef.createElement('section');
    root.className = 'live-view';

    const intro = documentRef.createElement('p');
    intro.className = 'form-note';
    intro.append(text(documentRef, 'Camera RTSP is copied into fragmented MP4 by FFmpeg and decoded by this browser. No re-encoding or camera setting changes.'));
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
    status.textContent = 'Press Start live video.';
    root.append(status);

    const player = documentRef.createElement('video');
    player.setAttribute('autoplay', '');
    player.setAttribute('muted', '');
    player.setAttribute('playsinline', '');
    player.setAttribute('controls', '');
    player.muted = true;
    const workspace = documentRef.createElement('div');
    workspace.className = 'live-workspace';
    const videoPanel = documentRef.createElement('div');
    videoPanel.className = 'live-video-panel';
    videoPanel.append(player);
    workspace.append(videoPanel);

    const ptzPanel = documentRef.createElement('aside');
    ptzPanel.className = 'live-ptz';
    const ptzContent = documentRef.createElement('div');
    ptzContent.className = 'live-ptz-content';
    ptzContent.append(text(documentRef, loadPtz ? 'Checking camera PTZ capability…' : 'PTZ control is not available.'));
    ptzPanel.append(ptzContent);
    workspace.append(ptzPanel);
    root.append(workspace);

    let controller = null;
    let objectUrl = null;
    let destroyed = false;
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
        controller?.abort();
        controller = null;
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
            status.textContent = 'Checking camera video format…';
            const response = await environment.fetch(`${endpoint}?${query}&action=info`, {
                signal: current.signal,
                cache: 'no-store',
            });
            const info = await response.json();
            if (!response.ok || !info.ok) throw new Error(info.error || 'Camera stream probe failed.');
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
                throw new Error(error.error || 'Could not start camera live video.');
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

    start.disabled = false;
    start.addEventListener('click', startPlayback);
    stop.addEventListener('click', () => cleanup('Stopped.'));
    const visibility = () => {
        if (documentRef.hidden) cancelMovement();
        if (documentRef.hidden && controller) cleanup('Stopped because this page became hidden.');
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
            cleanup();
            documentRef.removeEventListener?.('visibilitychange', visibility);
            environment.removeEventListener?.('blur', blur);
        },
    };
};
