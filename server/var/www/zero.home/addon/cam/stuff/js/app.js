import {CameraApi, CameraApiError} from './api.js?v=20261007-2';
import {
    chooseDefaultCamera,
    markActiveSection,
    SelectionGuard,
    setSectionButtonsBusy,
} from './state.js?v=20261005-4';
import {renderTree} from './tree.js?v=20261005-4';
import {renderEditableMenu} from './menu.js?v=20261007-1';
import {renderSmtpForm} from './smtp-form.js?v=20261006-1';
import {renderServiceForm} from './service-form.js?v=20261007-3';

const api = new CameraApi('./stuff/php/api.php');
const guard = new SelectionGuard();

const elements = {
    form: document.querySelector('#camera-form'),
    select: document.querySelector('#camera-select'),
    connect: document.querySelector('#connect-button'),
    reboot: document.querySelector('#reboot-button'),
    status: document.querySelector('#connection-status'),
    dashboard: document.querySelector('#dashboard'),
    identity: document.querySelector('#camera-identity'),
    sections: document.querySelector('#section-list'),
    title: document.querySelector('#section-title'),
    content: document.querySelector('#section-content'),
    refresh: document.querySelector('#refresh-section'),
    loadAll: document.querySelector('#load-all'),
};

let activeCamera = '';
let activeSection = '';

const setActiveSection = (sectionId) => {
    activeSection = sectionId;
    markActiveSection(elements.sections.querySelectorAll('.section-button'), sectionId);
};

const setBusy = (busy) => {
    elements.connect.disabled = busy;
    elements.reboot.disabled = busy || !activeCamera;
    elements.select.disabled = busy;
    elements.refresh.disabled = busy || !activeSection;
    elements.loadAll.disabled = busy || !activeCamera;
    setSectionButtonsBusy(elements.sections.querySelectorAll('.section-button'), busy);
    document.body.classList.toggle('is-loading', busy);
};

const showStatus = (message, kind = 'neutral') => {
    elements.status.textContent = message;
    elements.status.dataset.kind = kind;
};

const showError = (error) => {
    if (error?.name === 'AbortError') return;
    const message = error instanceof CameraApiError ? error.message : 'Unexpected camera error.';
    showStatus(message, 'error');
};

const showTree = (value) => {
    elements.content.replaceChildren(renderTree(value));
};

const renderIdentity = (status) => {
    elements.identity.replaceChildren(renderTree(status));
};

const loadSection = async (sectionId, sectionName, token) => {
    setActiveSection(sectionId);
    elements.title.textContent = sectionName;
    elements.content.textContent = 'Loading configuration…';
    setBusy(true);
    try {
        const data = await api.config(activeCamera, sectionId);
        if (!guard.isCurrent(token)) return;
        showTree(data);
        showStatus(`${sectionName} loaded.`, 'ok');
    } catch (error) {
        if (guard.isCurrent(token)) showError(error);
    } finally {
        if (guard.isCurrent(token)) setBusy(false);
    }
};

const loadSmtp = async (token) => {
    setActiveSection('smtp');
    elements.title.textContent = 'Email / SMTP';
    elements.content.textContent = 'Loading SMTP configuration…';
    setBusy(true);
    try {
        const data = await api.smtp(activeCamera);
        if (!guard.isCurrent(token)) return;
        const render = (model) => renderSmtpForm(model, {
            onReload: () => loadSmtp(guard.begin(activeCamera)),
            onSave: async (payload) => {
                const saveToken = guard.begin(activeCamera);
                setBusy(true);
                showStatus('Saving SMTP configuration…');
                try {
                    const saved = await api.saveSmtp(activeCamera, payload);
                    if (!guard.isCurrent(saveToken)) return;
                    elements.content.replaceChildren(render(saved));
                    showStatus('SMTP configuration saved and verified.', 'ok');
                } catch (error) {
                    if (guard.isCurrent(saveToken)) showError(error);
                } finally {
                    if (guard.isCurrent(saveToken)) setBusy(false);
                }
            },
            onTest: async () => {
                const testToken = guard.begin(activeCamera);
                setBusy(true);
                showStatus('Asking the camera to send a test email…');
                try {
                    await api.testSmtp(activeCamera);
                    if (guard.isCurrent(testToken)) {
                        showStatus('Camera accepted the SMTP test request. Check the configured recipient inbox.', 'ok');
                    }
                } catch (error) {
                    if (guard.isCurrent(testToken)) showError(error);
                } finally {
                    if (guard.isCurrent(testToken)) setBusy(false);
                }
            },
        });
        elements.content.replaceChildren(render(data));
        showStatus('SMTP configuration loaded.', 'ok');
    } catch (error) {
        if (guard.isCurrent(token)) showError(error);
    } finally {
        if (guard.isCurrent(token)) setBusy(false);
    }
};

const loadEditor = async (editorId, editorName, token) => {
    const sectionId = `editor:${editorId}`;
    setActiveSection(sectionId);
    elements.title.textContent = editorName;
    elements.content.textContent = `Loading ${editorName} configuration…`;
    setBusy(true);
    try {
        const data = await api.editor(activeCamera, editorId);
        if (!guard.isCurrent(token)) return;
        const render = (model) => renderServiceForm(model, {
            cameraId: activeCamera,
            onReload: () => loadEditor(editorId, editorName, guard.begin(activeCamera)),
            onWifiSearch: editorId === 'wifi'
                ? () => api.wifiAccessPoints(activeCamera)
                : null,
            onFtpTest: editorId === 'ftp' ? async () => {
                const testToken = guard.begin(activeCamera);
                setBusy(true);
                showStatus('Asking the camera to test its saved FTP connection…');
                try {
                    await api.testFtp(activeCamera);
                    if (guard.isCurrent(testToken)) {
                        const message = 'Camera accepted the FTP test request. Event uploads are separate.';
                        showStatus(message, 'ok');
                        return {ok: true, message};
                    }
                } catch (error) {
                    if (guard.isCurrent(testToken)) {
                        showError(error);
                        return {
                            ok: false,
                            message: error instanceof CameraApiError
                                ? error.message : 'Unexpected camera error.',
                        };
                    }
                } finally {
                    if (guard.isCurrent(testToken)) setBusy(false);
                }
            } : null,
            onSave: async (payload) => {
                const saveToken = guard.begin(activeCamera);
                setBusy(true);
                showStatus(`Saving ${editorName} configuration…`);
                try {
                    const saved = await api.saveEditor(activeCamera, editorId, payload);
                    if (!guard.isCurrent(saveToken)) return;
                    elements.content.replaceChildren(render(saved));
                    showStatus(`${editorName} configuration saved and verified.`, 'ok');
                } catch (error) {
                    if (guard.isCurrent(saveToken)) showError(error);
                } finally {
                    if (guard.isCurrent(saveToken)) setBusy(false);
                }
            },
        });
        elements.content.replaceChildren(render(data));
        showStatus(`${editorName} configuration loaded.`, 'ok');
    } catch (error) {
        if (guard.isCurrent(token)) showError(error);
    } finally {
        if (guard.isCurrent(token)) setBusy(false);
    }
};

const renderSections = (editors, sections) => {
    elements.sections.replaceChildren();
    elements.sections.append(...renderEditableMenu(editors, document, {
        onSmtp: () => loadSmtp(guard.begin(activeCamera)),
        onEditor: (id, name) => loadEditor(id, name, guard.begin(activeCamera)),
    }));
    const divider = document.createElement('div');
    divider.className = 'section-divider';
    divider.textContent = 'Read-only configuration';
    elements.sections.append(divider);
    for (const section of sections) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'section-button';
        button.dataset.sectionId = section.id;
        button.textContent = section.name;
        button.addEventListener('click', () => {
            loadSection(section.id, section.name, guard.begin(activeCamera));
        });
        elements.sections.append(button);
    }
};

const connect = async () => {
    const cameraId = elements.select.value;
    const token = guard.begin(cameraId);
    api.abortActive();
    activeCamera = cameraId;
    setActiveSection('');
    elements.dashboard.hidden = true;
    showStatus('Connecting to camera…');
    setBusy(true);

    try {
        const status = await api.status(cameraId);
        if (!guard.isCurrent(token)) return;
        renderIdentity(status);
        const editors = await api.editors(cameraId);
        if (!guard.isCurrent(token)) return;
        const sections = await api.sections(cameraId);
        if (!guard.isCurrent(token)) return;
        renderSections(editors, sections);
        elements.dashboard.hidden = false;
        showStatus('Connected. Choose a configuration section.', 'ok');
    } catch (error) {
        if (guard.isCurrent(token)) showError(error);
    } finally {
        if (guard.isCurrent(token)) setBusy(false);
    }
};

elements.form.addEventListener('submit', (event) => {
    event.preventDefault();
    connect();
});

elements.reboot.addEventListener('click', async () => {
    if (!activeCamera || !window.confirm('Reboot the selected camera now? It will be unavailable temporarily.')) {
        return;
    }
    const token = guard.begin(activeCamera);
    setBusy(true);
    showStatus('Sending reboot command…');
    try {
        await api.reboot(activeCamera);
        if (!guard.isCurrent(token)) return;
        elements.dashboard.hidden = true;
        showStatus('Reboot accepted. Wait for the camera to start, then click Connect.', 'ok');
    } catch (error) {
        if (guard.isCurrent(token)) showError(error);
    } finally {
        if (guard.isCurrent(token)) setBusy(false);
    }
});

elements.refresh.addEventListener('click', () => {
    if (activeSection === 'smtp') {
        loadSmtp(guard.begin(activeCamera));
        return;
    }
    if (activeSection.startsWith('editor:')) {
        const selected = [...elements.sections.querySelectorAll('.section-button')]
            .find((button) => button.dataset.sectionId === activeSection);
        loadEditor(
            activeSection.slice('editor:'.length),
            selected?.textContent || activeSection,
            guard.begin(activeCamera)
        );
        return;
    }
    const selected = [...elements.sections.querySelectorAll('.section-button')]
        .find((button) => button.dataset.sectionId === activeSection);
    loadSection(activeSection, selected?.textContent || activeSection, guard.begin(activeCamera));
});

elements.loadAll.addEventListener('click', async () => {
    const token = guard.begin(activeCamera);
    setActiveSection('all');
    elements.title.textContent = 'All sections';
    elements.content.textContent = 'Loading all configuration sections…';
    setBusy(true);
    try {
        const data = await api.config(activeCamera, 'all');
        if (!guard.isCurrent(token)) return;
        showTree(data);
        showStatus('All sections loaded.', 'ok');
    } catch (error) {
        if (guard.isCurrent(token)) showError(error);
    } finally {
        if (guard.isCurrent(token)) setBusy(false);
    }
});

const initialize = async () => {
    setBusy(true);
    try {
        const catalogue = await api.listCameras();
        elements.select.replaceChildren();
        for (const camera of catalogue.cameras) {
            const option = document.createElement('option');
            option.value = camera.id;
            option.textContent = camera.name;
            elements.select.append(option);
        }
        elements.select.value = chooseDefaultCamera(catalogue);
        showStatus('Select a camera and connect.');
    } catch (error) {
        showError(error);
    } finally {
        setBusy(false);
    }
};

initialize();
