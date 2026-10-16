const websocketUrl = (path, token, locationRef) => {
    const url = new URL(path, locationRef.href);
    url.protocol = url.protocol === 'https:' ? 'wss:' : 'ws:';
    url.searchParams.set('token', token);
    return url.toString();
};

export const createTalkClient = ({
    requestToken,
    socketPath = '/cam-talk',
    workletUrl = './stuff/js/mic-worklet.js?v=20261014-1',
    onState = () => {},
}, environment = globalThis) => {
    let socket = null;
    let stream = null;
    let context = null;
    let source = null;
    let worklet = null;
    let silence = null;
    let generation = 0;
    let state = 'closed';

    const report = (next, message = '') => {
        state = next;
        onState({state: next, message});
    };

    const releaseMedia = async () => {
        worklet?.port?.postMessage?.('stop');
        worklet?.disconnect?.();
        silence?.disconnect?.();
        source?.disconnect?.();
        for (const track of stream?.getTracks?.() || []) track.stop();
        if (context && context.state !== 'closed') await context.close();
        worklet = null;
        silence = null;
        source = null;
        stream = null;
        context = null;
    };

    const close = async (message = '', notify = true) => {
        generation++;
        const activeSocket = socket;
        socket = null;
        if (activeSocket && activeSocket.readyState < 2) activeSocket.close(1000, 'Talkback closed');
        await releaseMedia();
        if (notify) report('closed', message);
    };

    const beginCapture = async (current) => {
        const AudioContext = environment.AudioContext || environment.webkitAudioContext;
        if (!environment.navigator?.mediaDevices?.getUserMedia || !AudioContext || !environment.AudioWorkletNode) {
            throw new Error('This browser cannot capture microphone audio for talkback.');
        }
        const activeContext = new AudioContext();
        context = activeContext;
        const resume = activeContext.state === 'suspended' && typeof activeContext.resume === 'function'
            ? activeContext.resume()
            : Promise.resolve();
        const capture = environment.navigator.mediaDevices.getUserMedia({
            video: false,
            audio: {channelCount: 1, echoCancellation: true, noiseSuppression: true, autoGainControl: true},
        });
        const [activeStream] = await Promise.all([capture, resume]);
        if (generation !== current) {
            for (const track of activeStream?.getTracks?.() || []) track.stop();
            if (activeContext.state !== 'closed') await activeContext.close();
            return;
        }
        stream = activeStream;
        await activeContext.audioWorklet.addModule(workletUrl);
        if (generation !== current) return releaseMedia();
        source = activeContext.createMediaStreamSource(activeStream);
        worklet = new environment.AudioWorkletNode(activeContext, 'alina-cam-pcm', {
            processorOptions: {targetSampleRate: 8000, frameSamples: 320},
        });
        worklet.port.onmessage = ({data}) => {
            if (generation === current && socket?.readyState === 1 && data instanceof ArrayBuffer) socket.send(data);
        };
        source.connect(worklet);
        // AudioWorklet processing is pull-driven. Desktop browsers often keep a
        // detached microphone graph running, but mobile Chrome may suspend it.
        // Keep the graph connected to the output through a silent gain node so
        // PCM frames continue to reach the talkback socket without local echo.
        silence = activeContext.createGain();
        silence.gain.value = 0;
        worklet.connect(silence);
        silence.connect(activeContext.destination);
    };

    const start = async () => {
        if (state !== 'closed') return;
        const current = ++generation;
        report('connecting', 'Opening camera talkback…');
        try {
            // Mobile browsers only unlock microphone/Web Audio while handling
            // the original tap. Prime capture before awaiting network setup.
            const capture = beginCapture(current);
            const authorizationRequest = requestToken();
            const [authorization] = await Promise.all([authorizationRequest, capture]);
            if (generation !== current) return;
            socket = new environment.WebSocket(websocketUrl(socketPath, authorization.token, environment.location));
            socket.binaryType = 'arraybuffer';
            socket.onmessage = async ({data}) => {
                if (generation !== current || typeof data !== 'string') return;
                let event;
                try { event = JSON.parse(data); } catch { return; }
                if (event.state === 'ready') report('connecting', 'Camera accepted the talk request…');
                if (event.state === 'active') {
                    report('active', 'ON AIR');
                }
                if (event.state === 'busy' || event.state === 'error') {
                    await close('', false);
                    report(event.state, event.message || 'Camera talkback failed.');
                }
            };
            socket.onerror = () => {
                if (generation === current) report('error', 'Could not connect to the camera talkback service.');
            };
            socket.onclose = async () => {
                if (generation !== current) return;
                socket = null;
                await releaseMedia();
                if (state !== 'busy' && state !== 'error') report('closed', 'Talkback closed.');
            };
        } catch (error) {
            if (generation === current) {
                await close('', false);
                report('error', error?.message || 'Could not start camera talkback.');
            }
        }
    };

    return {
        toggle: () => state === 'closed' || state === 'error' || state === 'busy'
            ? (state = 'closed', start())
            : close('Talkback closed.'),
        stop: () => close('Talkback closed.'),
        destroy: () => close('', false),
        state: () => state,
    };
};
