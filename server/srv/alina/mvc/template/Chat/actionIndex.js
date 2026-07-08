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
            input.focus();
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
        if (isValidJsonString(text)) {
            const obj = JSON.parse(text);
            text = objToString(obj);
        }

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

    function isValidJsonString(str) {
        if (typeof str !== "string") return false;
        try {
            const parsed = JSON.parse(str);
            return typeof parsed === "object" && parsed !== null;
        } catch (e) {
            return false;
        }
    }

    function objToString(obj) {
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

    function messageToWeb(message) {
  if (typeof message !== 'string' || message.trim() === '') {
    return '';
  }

  const urlRegex = /(https?:\/\/[^\s"'<>()]+)/gi;
  const imageExtensions = /\.(png|jpg|jpeg|gif|webp|svg|bmp)\$/i;
  const videoExtensions = /\.(mp4|webm|mov|avi|m4v)\$/i;

  function escapeHtml(str) {
    if (typeof str !== 'string') return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Helper to create the copy button HTML safely
  function copyButtonHtml(url) {
    const safeUrl = escapeHtml(url);
    // Escape for use inside onclick="..."
    const jsSafeUrl = safeUrl.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    return `
      <button type="button"
        onclick="copyToClipboard('\${jsSafeUrl}')"
        style="margin-top:4px; padding:4px 8px; font-size:12px; cursor:pointer; background:#f0f0f0; border:1px solid #ccc; border-radius:4px;"
        title="Copy URL">
        🗎
      </button>
    `.trim();
  }

  return message.replace(urlRegex, (match) => {
    const safeUrl = escapeHtml(match);

    if (imageExtensions.test(match)) {
      return `
        <div style="display:inline-block; margin:5px 0; vertical-align:top;">
          <a href="\${safeUrl}" target="_blank" rel="noopener noreferrer" style="display:block; max-width:500px;">
            <img src="\${safeUrl}" style="max-width:100%; height:auto; display:block;" alt="image" />
          </a>
          \${copyButtonHtml(match)}
        </div>
      `.trim();
    }

    if (videoExtensions.test(match)) {
      return `
        <div style="display:inline-block; margin:5px 0; vertical-align:top;">
          <video controls playsinline style="max-width:500px; height:auto; display:block;">
            <source src="\${safeUrl}" type="video/mp4" />
            Your browser does not support the video tag.
          </video>
          \${copyButtonHtml(match)}
        </div>
      `.trim();
    }

    // Plain link: wrap in <a> and add copy button
    return `
      <div style="display:inline-block; margin:5px 0; vertical-align:top;">
        <a href="\${safeUrl}" target="_blank" rel="noopener noreferrer" style="word-break:break-all; color:#007bff; text-decoration:underline;">
          \${safeUrl}
        </a>
        \${copyButtonHtml(match)}
      </div>
    `.trim();
  });
}

});
