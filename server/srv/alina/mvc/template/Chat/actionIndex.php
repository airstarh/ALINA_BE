<style>
.wrapper {
    & #messages {
        border: 1px solid #ccc;
        padding: 10px;
        height: 300px;
        overflow-y: auto;
        font-family: Arial, sans-serif;
        background: #aaaaaa;
        color: #fff;
    }

    & .user-input {
        display: flex;

        & textarea {
            flex: 1 0;
            padding: 3px;
        }

        & button {
            padding: 8px 16px;
            cursor: pointer;
        }

        & .user-input-item {
            height: 11ch;
        }
    }
}
</style>
<div class="wrapper">
    <h2><?= ___('ЧRТ') ?></h2>
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
    console.log('Попытка подключения к:', wsUrl);

    let conn;

    try {
        conn = new WebSocket(wsUrl);
    } catch (e) {
        console.error('Ошибка создания WebSocket:', e);
        appendMessage('Ошибка инициализации WebSocket', 'red');
        return;
    }

    conn.onopen = function() {
        console.log('Connected');
        appendMessage('WSS available', 'green');
    };

    conn.onmessage = function(event) {
        console.log('📩 Получено:', event.data);
        appendMessage(event.data, 'black');
    };

    conn.onerror = function(error) {
        console.error('❌ Ошибка WebSocket:', error);
        appendMessage('Ошибка соединения (см. консоль)', 'red');
    };

    conn.onclose = function() {
        console.warn('⚠️ Соединение закрыто');
        appendMessage('Соединение закрыто', 'orange');
    };

    // --- ИСПРАВЛЕНИЕ ОШИБКИ NULL ---
    const sendBtn = document.getElementById('send-btn');
    const input = document.getElementById('input');

    if (!sendBtn || !input) {
        console.error('Критическая ошибка: Не найдены элементы #send-btn или #input');
        appendMessage('Ошибка: Элементы интерфейса не найдены! Проверьте HTML.', 'red');
        return; // Прерываем выполнение, чтобы не было addEventListener на null
    }

    function sendMessage() {
        const message = input.value.trim();
        if (message && conn && conn.readyState === WebSocket.OPEN) {
            conn.send(message);
            input.value = '';
        } else if (conn && conn.readyState !== WebSocket.OPEN) {
            alert('Нет активного соединения. Проверьте консоль.');
        } else {
            console.warn('WebSocket объект не инициализирован');
        }
    }

    sendBtn.addEventListener('click', sendMessage);

    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
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
