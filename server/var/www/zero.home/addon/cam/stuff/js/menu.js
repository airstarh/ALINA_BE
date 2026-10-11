export const EDITOR_GROUPS = [
    {heading: 'ACCESS', ids: ['access', 'live']},
    {heading: 'NETWORK', ids: [
        'wired_network', 'wifi', 'wifi_signal', 'dhcp', 'network_watchdog',
        'ip_adaptive', 'network_order', 'serial_bridge', 'rtsp',
    ]},
    {heading: 'STREAM', ids: [
        'main_stream', 'extra_stream', 'smart_h264', 'video_color_day',
        'video_color_night', 'video_widget', 'snapshot', 'recording',
    ]},
    {heading: 'PTZ', ids: ['ptz_resume', 'ptz_serial']},
    {heading: 'DETECTION', ids: [
        'human_detection', 'motion_detection', 'detection_tracking',
        'blind_detection', 'video_loss_detection', 'vehicle_detection',
    ]},
    {heading: 'ALARM', ids: [
        'alarm_server_1', 'alarm_server_2', 'alarm_server_3',
        'alarm_server_4', 'alarm_server_5', 'storage_failure',
        'storage_low_space', 'storage_missing', 'alarm_output',
        'local_alarm', 'network_abort_alarm', 'network_alarm',
        'ip_conflict_alarm',
    ]},
    {heading: 'SERVICES 3rd PART', ids: [
        'smtp', 'ftp', 'ntp', 'upnp', 'pms', 'das', 'multicast',
        'pppoe', 'nat', 'vendor_connections',
    ]},
    {heading: 'SERVICES 3rd PART DDNS', ids: [
        'ddns_1', 'ddns_2', 'ddns_3', 'ddns_4', 'ddns_5',
    ]},
    {heading: 'SERVICES HARDWARE', ids: ['rs485', 'uart_comm_1']},
    {heading: 'GENERAL', ids: [
        'general_preferences', 'locale_formats', 'defog', 'white_light',
        'overlay_indicators', 'osd_logo', 'output_volume', 'input_volume',
        'auto_maintain', 'fisheye', 'image_basic', 'image_advanced',
        'distortion', 'online_upgrade',
    ]},
];

export const renderEditableMenu = (editors, documentRef, {onSmtp, onEditor, onAccess, onLive}) => {
    const available = new Map(editors.map((editor) => [editor.id, editor]));
    available.set('access', {id: 'access', name: 'All points'});
    available.set('live', {id: 'live', name: 'live'});
    available.set('smtp', {id: 'smtp', name: 'Email / SMTP'});
    const nodes = [];

    const addButton = (editor, parent) => {
        const button = documentRef.createElement('button');
        button.type = 'button';
        button.className = 'section-button section-button-editor';
        button.dataset.sectionId = ['smtp', 'access', 'live'].includes(editor.id) ? editor.id : `editor:${editor.id}`;
        button.textContent = editor.name;
        button.addEventListener('click', () => {
            if (editor.id === 'access') onAccess();
            else if (editor.id === 'live') onLive?.();
            else if (editor.id === 'smtp') onSmtp();
            else onEditor(editor.id, editor.name);
        });
        parent.append(button);
    };

    const addGroup = (title) => {
        const section = documentRef.createElement('details');
        section.className = 'section-menu-group';
        const heading = documentRef.createElement('summary');
        heading.className = 'section-group-heading';
        heading.textContent = title;
        section.append(heading);
        const items = documentRef.createElement('div');
        items.className = 'section-group-items';
        section.append(items);
        nodes.push(section);
        return items;
    };

    for (const group of EDITOR_GROUPS) {
        const visible = group.ids.filter((id) => available.has(id));
        if (visible.length === 0) continue;
        const items = addGroup(group.heading);
        for (const id of visible) {
            addButton(available.get(id), items);
            available.delete(id);
        }
    }

    // Keep future camera-supported editors accessible until they are grouped.
    if (available.size) {
        const items = addGroup('OTHER');
        for (const editor of available.values()) addButton(editor, items);
    }
    return nodes;
};
