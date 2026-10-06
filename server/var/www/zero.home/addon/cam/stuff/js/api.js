export class CameraApiError extends Error {
    constructor(code, message, status = 0) {
        super(message);
        this.name = 'CameraApiError';
        this.code = code;
        this.status = status;
    }
}

export class CameraApi {
    #baseUrl;
    #fetch;
    #controller = null;

    constructor(baseUrl, fetchImpl = globalThis.fetch.bind(globalThis)) {
        this.#baseUrl = baseUrl;
        this.#fetch = fetchImpl;
    }

    listCameras() {
        return this.#request({action: 'cameras'});
    }

    status(cameraId) {
        return this.#request({action: 'status', camera: cameraId});
    }

    sections(cameraId) {
        return this.#request({action: 'sections', camera: cameraId});
    }

    config(cameraId, section) {
        return this.#request({action: 'config', camera: cameraId, section});
    }

    smtp(cameraId) {
        return this.#request({action: 'smtp', camera: cameraId});
    }

    saveSmtp(cameraId, smtp) {
        return this.#request(
            {action: 'smtp-save', camera: cameraId},
            {method: 'POST', body: smtp}
        );
    }

    editors(cameraId) {
        return this.#request({action: 'editors', camera: cameraId});
    }

    editor(cameraId, editorId) {
        return this.#request({action: 'editor', camera: cameraId, editor: editorId});
    }

    saveEditor(cameraId, editorId, values) {
        return this.#request(
            {action: 'editor-save', camera: cameraId, editor: editorId},
            {method: 'POST', body: values}
        );
    }

    wifiAccessPoints(cameraId) {
        return this.#request({action: 'wifi-aps', camera: cameraId});
    }

    abortActive() {
        this.#controller?.abort();
        this.#controller = null;
    }

    async #request(parameters, options = {}) {
        this.abortActive();
        const controller = new AbortController();
        this.#controller = controller;
        const query = new URLSearchParams(parameters);

        let response;
        try {
            response = await this.#fetch(`${this.#baseUrl}?${query}`, {
                method: options.method || 'GET',
                headers: {
                    'Accept': 'application/json',
                    ...(options.body === undefined ? {} : {'Content-Type': 'application/json'}),
                },
                ...(options.body === undefined ? {} : {body: JSON.stringify(options.body)}),
                cache: 'no-store',
                signal: controller.signal,
            });
        } catch (error) {
            if (error?.name === 'AbortError') {
                throw error;
            }
            throw new CameraApiError('network_error', 'Could not reach the camera API.');
        }

        let payload;
        try {
            payload = await response.json();
        } catch {
            throw new CameraApiError('invalid_response', 'Camera API returned invalid JSON.', response.status);
        }

        if (!response.ok || payload?.ok !== true) {
            throw new CameraApiError(
                payload?.error?.code || 'request_failed',
                payload?.error?.message || 'Camera request failed.',
                response.status
            );
        }

        return payload.data;
    }
}
