const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const add = (parent, tag, className = '', text = '') => {
    const node = parent.ownerDocument.createElement(tag);
    if (className) node.className = className;
    if (text) node.textContent = text;
    parent.append(node);
    return node;
};

const timeSeconds = (value, allowEndOfDay) => {
    const match = /^(\d{2}):(\d{2})(?::(\d{2}))?$/.exec(value);
    if (!match) return null;
    const hours = Number(match[1]);
    const minutes = Number(match[2]);
    const seconds = Number(match[3] || 0);
    if (minutes > 59 || seconds > 59 || hours > 24) return null;
    if (hours === 24 && (!allowEndOfDay || minutes !== 0 || seconds !== 0)) return null;
    return hours * 3600 + minutes * 60 + seconds;
};

export function renderPeriodSection(form, periods) {
    if (!Array.isArray(periods) || periods.length !== DAYS.length) return null;

    const section = add(form, 'details', 'period-section');
    add(section, 'summary', 'period-summary', 'Active periods');
    add(section, 'p', 'period-intro', 'Choose a day and edit its enabled time slots. Up to six slots are stored per day; disabled slots keep their times.');
    const navigation = add(section, 'div', 'period-days');
    const pages = add(section, 'div', 'period-pages');
    const dayButtons = [];
    const dayPages = [];
    const controls = [];

    const showDay = (selected) => {
        dayButtons.forEach((button, day) => {
            button.classList.toggle('is-active', day === selected);
            button.setAttribute('aria-pressed', day === selected ? 'true' : 'false');
            dayPages[day].hidden = day !== selected;
        });
    };

    periods.forEach((slots, day) => {
        const dayButton = add(navigation, 'button', 'period-day-button', DAYS[day].slice(0, 3));
        dayButton.type = 'button';
        dayButton.setAttribute('aria-label', DAYS[day]);
        dayButton.addEventListener('click', () => showDay(day));
        dayButtons.push(dayButton);

        const page = add(pages, 'div', 'period-day-page');
        add(page, 'h4', '', DAYS[day]);
        dayPages.push(page);
        slots.forEach((original, slot) => {
            const row = add(page, 'div', 'period-slot');
            const toggle = add(row, 'label', 'period-slot-toggle');
            const enabled = add(toggle, 'input');
            enabled.type = 'checkbox';
            enabled.checked = original.enabled;
            add(toggle, 'span', '', `Slot ${slot + 1}`);

            const start = add(row, 'input', 'form-input');
            start.type = 'text';
            start.inputMode = 'numeric';
            start.value = original.start;
            start.placeholder = 'HH:MM';
            start.setAttribute('aria-label', `${DAYS[day]} slot ${slot + 1} start`);
            add(row, 'span', 'period-arrow', '→');
            const end = add(row, 'input', 'form-input');
            end.type = 'text';
            end.inputMode = 'numeric';
            end.value = original.end;
            end.placeholder = 'HH:MM';
            end.setAttribute('aria-label', `${DAYS[day]} slot ${slot + 1} end`);

            const refresh = () => {
                start.disabled = !enabled.checked;
                end.disabled = !enabled.checked;
            };
            enabled.addEventListener('change', refresh);
            start.addEventListener('input', () => start.setCustomValidity(''));
            end.addEventListener('input', () => end.setCustomValidity(''));
            refresh();
            controls.push({day, slot, original, enabled, start, end});
        });
    });
    showDay(0);

    return {
        collectChanges() {
            const changes = [];
            for (const control of controls) {
                const {day, slot, original, enabled, start, end} = control;
                start.setCustomValidity('');
                end.setCustomValidity('');
                const startValue = enabled.checked ? start.value.trim() : original.start;
                const endValue = enabled.checked ? end.value.trim() : original.end;
                if (enabled.checked === original.enabled
                    && startValue === original.start && endValue === original.end) continue;

                const from = timeSeconds(startValue, false);
                const to = timeSeconds(endValue, true);
                const invalid = from === null ? start : to === null || from >= to ? end : null;
                if (invalid) {
                    section.open = true;
                    showDay(day);
                    invalid.setCustomValidity('Use HH:MM (or HH:MM:SS), with an end later than the start. 24:00 is allowed only as the end.');
                    invalid.reportValidity();
                    return null;
                }
                changes.push({
                    day, slot, expected: original.raw,
                    enabled: enabled.checked, start: startValue, end: endValue,
                });
            }
            return changes;
        },
        describe(change) {
            return `${DAYS[change.day]} slot ${change.slot + 1}: `
                + `${change.enabled ? 'Enabled' : 'Disabled'} ${change.start}–${change.end}`;
        },
    };
}
