export function chooseDefaultCamera(catalogue) {
    const cameras = Array.isArray(catalogue?.cameras) ? catalogue.cameras : [];
    const configured = cameras.some((camera) => camera.id === catalogue?.default);
    return configured ? catalogue.default : (cameras[0]?.id || '');
}

export function markActiveSection(buttons, activeSection) {
    for (const button of buttons) {
        const isActive = button.dataset.sectionId === activeSection;
        button.classList.toggle('is-active', isActive);
        if (isActive) button.setAttribute('aria-current', 'page');
        else button.removeAttribute('aria-current');
    }
}

export class SelectionGuard {
    #generation = 0;

    begin(cameraId) {
        return Object.freeze({cameraId, generation: ++this.#generation});
    }

    isCurrent(token) {
        return token?.generation === this.#generation;
    }
}
