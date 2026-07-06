<style>
.wrapper {
    height: 100vh;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    flex-wrap: nowrap;
    justify-content: flex-end;
    align-content: stretch;
    align-items: stretch;

    & #messages {
        flex: 0 0 90vh;
        height: 90vh;
        white-space: pre-wrap;
        word-break: break-word;
        border: 1px solid #ccc;
        padding: 10px;
        overflow-y: auto;
        background-color: #222222;
        color: #dddddd;
    }

    & .user-input {
        flex: 0 0 10vh;
        height: 10vh;
        display: flex;

        & textarea {
            flex: 1 0;
            padding: 3px;
        }

        & button {
            padding: 8px 16px;
            cursor: pointer;
        }
    }
}
</style>
<div class="wrapper">
    <div id="messages"></div>
    <div class="user-input">
        <textarea class="user-input-item" id="input" autocomplete="off" placeholder=""></textarea>
        <button class="user-input-item" id="send-btn"><?= ___('Send') ?></button>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const host = window.location.host;
    const protocol = window.location.protocol === 'http:' ? 'ws:' : 'wss:';
    const wsUrl = `${protocol}//${host}/ws`;
    console.log('Connecting...:', wsUrl);

    let conn;

    try {
        conn = new WebSocket(wsUrl);
    } catch (e) {
        console.error('Ошибка создания WebSocket:', e);
        appendMessage('Ошибка инициализации WebSocket', 'red');
        return;
    }

    conn.onopen = function() {
        console.log('WSS available');
        appendMessage('WSS available', 'green');
    };

    conn.onmessage = function(event) {
        console.log('📩 Received:', event.data);
        appendMessage(event.data, '#dddddd');
    };

    conn.onerror = function(error) {
        console.error('❌ WebSocket error:', error);
        appendMessage('❌ WebSocket error:', 'red');
    };

    conn.onclose = function() {
        console.warn('⚠️ Connection closed');
        appendMessage('⚠️ Connection closed', 'orange');
    };

    const sendBtn = document.getElementById('send-btn');
    const input = document.getElementById('input');

    if (!sendBtn || !input) {
        console.error('DOM critical error.');
        appendMessage('DOM critical error.', 'red');
        return;
    }

    function sendMessage() {
        const message = input.value.trim();
        if (message && conn && conn.readyState === WebSocket.OPEN) {
            conn.send(message);
            input.value = '';
        } else if (conn && conn.readyState !== WebSocket.OPEN) {
            alert('No connection. Check console.');
        } else {
            console.warn('WebSocket is not initialized');
        }
    }

    sendBtn.addEventListener('click', sendMessage);

    input.addEventListener('keypress', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            sendMessage();
        }
    });

    function appendMessage(text, color) {
        const messagesDiv = document.getElementById('messages');
        if (!messagesDiv) return; // Защита от null

        const div = document.createElement('div');
        div.style.color = color;
        div.style.marginBottom = '4px';
        div.textContent = text;
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
});
</script>
