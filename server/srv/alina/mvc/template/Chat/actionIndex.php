<h2>Chat</h2>
<div id="messages" style="border:1px solid #ccc; padding:10px; height:300px; overflow-y:auto;"></div>
<input type="text" id="input" autocomplete="off" />
<button onclick="sendMessage()">Отправить</button>

<script>
const conn = new WebSocket('ws://zero.home:8080'); // Адрес вашего сервера

    conn.onopen = function() {
        console.log('Соединение установлено');
    };

    conn.onmessage = function(event) {
        const messagesDiv = document.getElementById('messages');
        messagesDiv.innerHTML += event.data + '<br>';
    };

    function sendMessage() {
        const input = document.getElementById('input');
        const message = input.value;
        if (message) {
            conn.send(message);
            input.value = '';
        }
    }
</script>
