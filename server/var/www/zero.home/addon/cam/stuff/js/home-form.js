import {renderLiveForm} from './live-form.js?v=20261016-4';

const text = (documentRef, value) => documentRef.createTextNode(value);

export const renderHomeForm = (
    {cameras, prepareEndpoint, recordEndpoint, snapshotEndpoint, relaySocketPath = '/cam-stream/api/ws',
        requestTalkToken, onGoLive},
    documentRef = document,
    environment = globalThis,
) => {
    const root = documentRef.createElement('section');
    root.className = 'home-camera-grid';
    const views = [];

    for (const camera of cameras) {
        const card = documentRef.createElement('article');
        card.className = 'home-camera-card';
        const title = documentRef.createElement('h3');
        title.append(text(documentRef, camera.name));
        card.append(title);
        const view = renderLiveForm({
            cameraId: camera.id,
            prepareEndpoint,
            recordEndpoint,
            snapshotEndpoint,
            relaySocketPath,
            autoStart: true,
            compact: true,
            talkback: camera.talkback === true,
            requestTalkToken: typeof requestTalkToken === 'function'
                ? () => requestTalkToken(camera.id)
                : null,
            onGoLive,
        }, documentRef, environment);
        views.push(view);
        card.append(view.element);
        root.append(card);
    }

    return {
        element: root,
        destroy() {
            for (const view of views) view.destroy();
        },
    };
};
