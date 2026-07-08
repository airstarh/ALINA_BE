// @ts-nocheck
document.addEventListener("DOMContentLoaded", () => {
    const host = window.location.host;
    const protocol = window.location.protocol === "http:" ? "ws:" : "wss:";
    const wsUrl = `${protocol}//${host}/ws`;
    console.log("Connecting...:", wsUrl);

    let conn;

    try {
        conn = new WebSocket(wsUrl);
    } catch (e) {
        console.error("WebSocket creation error", e);
        appendMessage("WebSocket creation error", "red");
        return;
    }

    conn.onopen = function () {
        console.log("WSS available");
        appendMessage("WSS available", "green");
    };

    conn.onmessage = function (event) {
        console.log("📩 Received:", event.data);
        appendMessage(event.data, "#dddddd");
    };

    conn.onerror = function (error) {
        console.error("❌ WebSocket error:", error);
        appendMessage("❌ WebSocket error:", "red");
    };

    conn.onclose = function () {
        console.warn("⚠️ Connection closed");
        appendMessage("⚠️ Connection closed", "orange");
    };

    const sendBtn = document.getElementById("send-btn");
    const input = document.getElementById("input");

    if (!sendBtn || !input) {
        console.error("DOM critical error.");
        appendMessage("DOM critical error.", "red");
        return;
    }

    function sendMessage() {
        const message = input.value.trim();
        xxx = JSON.stringify({ msg: message, CurrentUser: ALINA.CurrentUser });
        if (message && conn && conn.readyState === WebSocket.OPEN) {
            conn.send(xxx);
            input.value = "";
        } else if (conn && conn.readyState !== WebSocket.OPEN) {
            alert("No connection. Check console.");
        } else {
            console.warn("WebSocket is not initialized");
        }
    }

    sendBtn.addEventListener("click", sendMessage);

    input.addEventListener("keypress", function (e) {
        if (e.ctrlKey && e.key === "Enter") {
            sendMessage();
        }
    });

    function appendMessage(text, color) {
        console.log(text);
        if (isValidJsonString(text)) {
            const obj = JSON.parse(text);
            text = objTostring(obj);
        }

        const messagesDiv = document.getElementById("messages");
        if (!messagesDiv) return; // Защита от null

        const div = document.createElement("div");
        div.style.color = color;
        div.style.marginBottom = "4px";
        div.innerHTML = text;
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function isValidJsonString(str) {
        if (typeof str !== "string") return false;
        try {
            const parsed = JSON.parse(str);
            return typeof parsed === "object" && parsed !== null;
        } catch (e) {
            return false;
        }
    }

    function objTostring(obj) {
        const res = [
            "<span>",
            `<img src='${obj.CurrentUser.emblem}' style="max-height:50px;max-width:50px;" />`,
            "</span>",
            "<span>",
            obj.msg,
            "</span>",
        ];

        return res.join(' ');
    }
});
