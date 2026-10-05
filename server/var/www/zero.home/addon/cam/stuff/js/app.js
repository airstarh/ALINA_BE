import {CameraApi, CameraApiError} from './api.js';
import {
    chooseDefaultCamera,
    markActiveSection,
    SelectionGuard,
    setSectionButtonsBusy,
} from './state.js';
import {renderTree} from './tree.js';

const api = new CameraApi('./stuff/php/api.php');
const guard = new SelectionGuard();

const elements = {
    form: document.querySelector('#camera-form'),
    select: document.querySelector('#camera-select'),
    connect: document.querySelector('#connect-button'),
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

const renderSections = (sections) => {
    elements.sections.replaceChildren();
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
        const sections = await api.sections(cameraId);
        if (!guard.isCurrent(token)) return;
        renderSections(sections);
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

elements.refresh.addEventListener('click', () => {
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
