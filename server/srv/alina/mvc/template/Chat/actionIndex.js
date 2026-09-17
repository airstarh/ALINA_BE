// Legacy PHP page, compatible with the public message format from ChatServer.
document.addEventListener('DOMContentLoaded', () => {
    const channel = new URLSearchParams(location.search).get('channel')?.trim() || '1';
    const wsUrl = `${location.protocol === 'http:' ? 'ws:' : 'wss:'}//${location.host}/ws`;
    const input = document.getElementById('input');
    const sendBtn = document.getElementById('send-btn');
    const messages = document.getElementById('messages');
    const connectionStatus = document.getElementById('connection-status');
    if (!input || !sendBtn || !messages) return;
    let conn = null;
    let retryTimer = null;
    let retryCount = 0;
    let stopped = false;

    function setStatus(text, color) {
        if (connectionStatus) {
            connectionStatus.textContent = text;
            connectionStatus.style.color = color;
        }
        sendBtn.disabled = conn?.readyState !== WebSocket.OPEN;
    }
    function safeUrl(value) {
        if (typeof value !== 'string' || !value.trim()) return '';
        try {
            const url = new URL(value, location.origin);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
        } catch { return ''; }
    }
    function createConnection() {
        if (stopped) return;
        setStatus('Connecting…', 'orange');
        let active;
        try {
            active = new WebSocket(wsUrl);
            conn = active;
        } catch { scheduleRetry(); return; }
        active.onopen = async () => {
            retryCount = 0;
            try { await ALINA.getCurrentUser(); } catch (e) { console.error('Chat identity refresh failed', e); }
            if (stopped || conn !== active || active.readyState !== WebSocket.OPEN) return;
            active.send(JSON.stringify({msg: `JOIN:${channel}`, channel, CurrentUser: publicUser(), stateChatJustOpened: 1}));
            setStatus('Connected', '#75ddb0');
        };
        active.onmessage = event => {
            let data;
            try { data = JSON.parse(event.data); } catch { return; }
            if (data?.error) { setStatus(data.error, 'orange'); return; }
            if (Array.isArray(data)) {
                // A JOIN gets a history snapshot only on this connection.
                messages.replaceChildren();
                for (let item of data) {
                    if (typeof item === 'string') {
                        try { item = JSON.parse(item); } catch { /* Render legacy text safely. */ }
                    }
                    appendMessage(item);
                }
            } else { appendMessage(data); }
        };
        active.onerror = () => setStatus('Connection interrupted', 'orange');
        active.onclose = () => {
            if (stopped || conn !== active) return;
            conn = null;
            scheduleRetry();
        };
    }
    function scheduleRetry() {
        if (stopped || retryTimer !== null) return;
        const delay = Math.min(11000 * 2 ** Math.min(retryCount++, 3), 60000);
        setStatus(`Reconnecting in ${delay / 1000}s`, 'orange');
        retryTimer = setTimeout(() => { retryTimer = null; createConnection(); }, delay);
    }
    function publicUser() {
        return Object.fromEntries(['id', 'firstname', 'lastname', 'nickname', 'emblem'].map(key => [key, ALINA.CurrentUser?.[key] ?? null]));
    }
    function sendMessage() {
        const msg = input.value.trim();
        if (!msg) return;
        if (new TextEncoder().encode(msg).length > 16000) {
            setStatus('Message too long (maximum 16000 bytes)', 'orange');
            return;
        }
        if (conn?.readyState !== WebSocket.OPEN) {
            setStatus('No active connection. Your draft is preserved.', 'orange');
            return;
        }
        try {
            conn.send(JSON.stringify({msg, channel, CurrentUser: publicUser(), stateChatJustOpened: 0}));
            input.value = '';
            input.focus();
        } catch { setStatus('Could not send. Your draft is preserved.', 'orange'); }
    }
    function renderText(container, text) {
        const pattern = /https?:\/\/[^\s"'<>()]+/gi;
        let cursor = 0;
        for (const match of text.matchAll(pattern)) {
            container.append(document.createTextNode(text.slice(cursor, match.index)));
            const url = safeUrl(match[0]);
            if (!url) {
                container.append(document.createTextNode(match[0]));
            } else {
                const path = new URL(url).pathname;
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                if (/\.(png|jpe?g|gif|webp|svg|bmp)$/i.test(path)) {
                    const image = document.createElement('img');
                    image.src = url;
                    image.alt = 'Shared image';
                    image.className = 'chat-shared-media';
                    link.append(image);
                } else {
                    if (/\.(mp4|webm|mov|avi|m4v)$/i.test(path)) {
                        const video = document.createElement('video');
                        video.src = url;
                        video.controls = true;
                        video.playsInline = true;
                        video.className = 'chat-shared-media';
                        container.append(video);
                    }
                    link.textContent = url;
                }
                container.append(link);
                const copy = document.createElement('button');
                copy.type = 'button';
                copy.textContent = '⧉';
                copy.title = 'Copy URL';
                copy.setAttribute('aria-label', 'Copy URL');
                copy.addEventListener('click', async () => {
                    try { await navigator.clipboard.writeText(url); } catch { setStatus('Could not copy URL', 'orange'); }
                });
                container.append(copy);
            }
            cursor = match.index + match[0].length;
        }
        container.append(document.createTextNode(text.slice(cursor)));
    }
    function appendMessage(message) {
        if (!message?.id && typeof message?.msg === 'string' && message.msg.startsWith('JOIN:')) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'chat-message-wrapper';
        if (message && typeof message === 'object') {
            const user = message.CurrentUser || {};
            const own = Number(user.id) > 0 && String(user.id) === String(ALINA.CurrentUser?.id);
            const content = document.createElement('div');
            content.className = `p-2 rounded${own ? ' this-user' : ''}`;
            const heading = document.createElement('div');
            heading.className = 'user-data d-flex';
            const avatar = document.createElement('img');
            avatar.src = safeUrl(user.emblem) || '/noimage.png';
            avatar.alt = '';
            avatar.className = 'user-avatar a-circle';
            const name = document.createElement('strong');
            name.className = 'user-name';
            name.textContent = user.name || user.nickname || [user.firstname, user.lastname].filter(Boolean).join(' ') || 'Guest';
            const time = document.createElement('time');
            time.className = 'user-time';
            const date = new Date(message.timestamp || Date.now());
            time.textContent = Number.isNaN(date.getTime()) ? '' : date.toLocaleString();
            const body = document.createElement('div');
            body.className = 'user-message';
            renderText(body, String(message.msg || ''));
            heading.append(avatar, name, time);
            content.append(heading, body);
            wrapper.append(content);
        } else { wrapper.textContent = String(message ?? ''); }
        messages.append(wrapper);
        messages.scrollTop = messages.scrollHeight;
    }
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', event => {
        if (event.ctrlKey && event.key === 'Enter' && !event.isComposing) {
            event.preventDefault();
            sendMessage();
        }
    });
    window.addEventListener('pagehide', () => {
        stopped = true;
        clearTimeout(retryTimer);
        retryTimer = null;
        conn?.close();
        conn = null;
    });
    window.addEventListener('pageshow', event => {
        if (event.persisted) { stopped = false; createConnection(); }
    });
    createConnection();
});
