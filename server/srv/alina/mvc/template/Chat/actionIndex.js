// @ts-nocheck
document.addEventListener("DOMContentLoaded", () => {
    const host = window.location.host;
    const protocol = window.location.protocol === "http:" ? "ws:" : "wss:";

    // Channel logic: default '1', override from ?channel=...
    const urlParams = new URLSearchParams(window.location.search);
    const currentChannel = urlParams.get("channel") || "1";

    const wsUrl = `${protocol}//${host}/ws`;
    console.log(
        "Initializing WebSocket connection:",
        wsUrl,
        "channel:",
        currentChannel,
    );

    let conn = null;
    let retryCount = 0;
    const maxRetries = 10;
    const retryDelayMs = 11000; // 11 seconds

    // States
    let stateChatJustOpened = 1;

    function createConnection() {
        try {
            conn = new WebSocket(wsUrl);
        } catch (e) {
            console.error("WebSocket creation error", e);
            appendMessage("WebSocket creation error", "red");
            scheduleRetry();
            return;
        }

        conn.onopen = async function () {
            console.log("onopen:WSS available");
            retryCount = 0; // Reset retries on successful connect
            appendMessage("WSS available", "green");
            await ALINA.getCurrentUser();
            // Send initial join with channel and state
            sendMessage(`JOIN:${currentChannel}`);
        };

        conn.onmessage = function (event) {
            console.log("onmessage");
            const text = event.data;

            if (!isValidJsonString(text)) {
                // Handle non-JSON (could be legacy single-string messages)
                appendMessage(text, "#dddddd");
                return;
            }

            const data = JSON.parse(text);

            if (Array.isArray(data)) {
                for (const [key, item] of data.entries()) {
                    const msg = isValidJsonString(item)
                        ? objToString(JSON.parse(item))
                        : String(item);

                    appendMessage(msg, "#dddddd");
                }
                return;
            }
            // {}
            appendMessage(objToString(data), "#dddddd");
        };

        conn.onerror = function (error) {
            console.error("❌ WebSocket error:", error);
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

    function sendMessage(rawInput) {
        let message = "";

        message = typeof rawInput === "string" ? rawInput : input.value.trim();

        const payloadObj = {
            msg: message,
            CurrentUser: ALINA.CurrentUser,
            stateChatJustOpened: stateChatJustOpened,
            channel: currentChannel, // Attach channel to every message
        };

        // For JOIN command, we still send JSON but keep the msg field as the JOIN token
        const payload = JSON.stringify(payloadObj);

        if (message && conn && conn.readyState === WebSocket.OPEN) {
            conn.send(payload);
            input.value = "";
            input.focus();
            stateChatJustOpened = 0;
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
        const messagesDiv = document.getElementById("messages");
        if (!messagesDiv) return;

        const div = document.createElement("div");
        div.className = "chat-message-wrapper";
        div.style.color = color;
        div.style.marginBottom = "22px";
        div.innerHTML = text;
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function objToString(obj) {
        // Legacy compat: if old single-string format arrives, handle gracefully
        if (typeof obj === "string") {
            return `<div class="p-2 rounded">${obj}</div>`;
        }

        const id = obj.CurrentUser?.id || -1;
        const emblem = obj.CurrentUser?.emblem || "/noimage.png";
        const name = userName(obj.CurrentUser);
        const time = currentDateTIme();
        const message = obj?.msg || "[unrecognized]";
        const messageWeb = messageToWeb(message);
        const msgClassName = id === ALINA.CurrentUser?.id ? "this-user" : "";

        const html = `
    <div class="p-2 rounded ${msgClassName}">
    <span class="user-data d-flex">
     <span class="mr-3">
      <img src="${emblem}" class="user-avatar" alt="avatar" />
     </span>
     <span>
      <span class="user-name">${name}</span>
      &nbsp;
      <span class="user-time">${time}</span>
     </span>
    </span>
    <div class="user-message">${messageWeb}</div>
    </div>
  `;
        return html.trim().replace(/\s+/g, " ").replace(/> </g, "><");
    }

    function userName(CurrentUser) {
        const firstname = CurrentUser?.firstname || "XXX";
        const lastname = CurrentUser?.lastname || "";
        const parts = [firstname, lastname];

        return parts.filter((part) => part != null && part !== "").join(" ");
    }

    function currentDateTIme() {
        const now = new Date();
        const pad = (n) => String(n).padStart(2, "0");
        const dateStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        return dateStr;
    }
});
