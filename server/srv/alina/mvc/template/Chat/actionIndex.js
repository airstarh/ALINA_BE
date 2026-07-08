// @ts-nocheck
document.addEventListener("DOMContentLoaded", () => {
    const host = window.location.host;
    const protocol = window.location.protocol === "http:" ? "ws:" : "wss:";
    const wsUrl = `${protocol}//${host}/ws`;
    console.log("Initializing WebSocket connection:", wsUrl);

    let conn = null;
    let retryCount = 0;
    const maxRetries = 3;
    const retryDelayMs = 11000; // 11 seconds

    function createConnection() {
        try {
            conn = new WebSocket(wsUrl);
        } catch (e) {
            console.error("WebSocket creation error", e);
            appendMessage("WebSocket creation error", "red");
            scheduleRetry();
            return;
        }

        conn.onopen = function () {
            console.log("WSS available");
            retryCount = 0; // Reset retries on successful connect
            appendMessage("WSS available", "green");
        };

        conn.onmessage = function (event) {
            console.log("📩 Received:", event.data);
            appendMessage(event.data, "#dddddd");
        };

        conn.onerror = function (error) {
            console.error("❌ WebSocket error:", error);
            // Do not treat error as immediate disconnect; onclose will handle reconnection
        };

        conn.onclose = function () {
            console.warn("⚠️ Connection closed");
            appendMessage("⚠️ Connection closed", "orange");
            scheduleRetry();
        };
    }

    function scheduleRetry() {
        if (retryCount >= maxRetries) {
            console.error("Max retries reached. Please reload the page.");
            appendMessage(
                "❌ Max retries reached. Please <b>reload the page</b>.",
                "red",
            );
            return;
        }

        retryCount++;
        const timeLeft = ((maxRetries - retryCount + 1) * retryDelayMs) / 1000;
        console.log(
            `Retry attempt ${retryCount} of ${maxRetries} in ${retryDelayMs / 1000}s`,
        );
        appendMessage(
            `⏳ Reconnecting in ${retryDelayMs / 1000}s (attempt ${retryCount}/${maxRetries})`,
            "orange",
        );

        setTimeout(() => {
            createConnection();
        }, retryDelayMs);
    }

    // Start first connection
    createConnection();

    const sendBtn = document.getElementById("send-btn");
    const input = document.getElementById("input");

    if (!sendBtn || !input) {
        console.error("DOM critical error.");
        appendMessage("DOM critical error.", "red");
        return;
    }

    function sendMessage() {
        const message = input.value.trim();
        const payload = JSON.stringify({
            msg: message,
            CurrentUser: ALINA.CurrentUser,
        });

        if (message && conn && conn.readyState === WebSocket.OPEN) {
            conn.send(payload);
            input.value = "";
        } else if (conn && conn.readyState !== WebSocket.OPEN) {
            alert("No active connection. Waiting for reconnect...");
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
        if (!messagesDiv) return;

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
        const emblem = obj.CurrentUser?.emblem || "/noimage.png";
        const name = userName(obj.CurrentUser);
        const res = [
            "<span>",
            `<img src='${emblem}' style="max-height:50px;max-width:50px;" class="user-avatar" />`,
            name,
            ": ",
            "</span>",
            "<div>",
            obj?.msg || "[unrecognized]",
            "</div>",
        ];
        return res.join("");
    }

    function userName(CurrentUser) {
        const firstname = CurrentUser?.firstname || "XXX";
        const lastname = CurrentUser?.lastname || "";
        const parts = [firstname, lastname];

        return parts.filter((part) => part != null && part !== "").join(" ");
    }
});
