// === ГЛОБАЛЬНЫЕ ФУНКЦИИ (Вставить ПЕРЕД document.addEventListener) ===

window.copyToClipboard = async function (text) {
    try {
        await navigator.clipboard.writeText(text);
        // Здесь можно добавить красивую всплывашку (toast), если есть UI библиотека
        console.log("URL copied:", text);
    } catch (e) {
        console.error("Copy failed:", e);
        alert("Failed to copy URL");
    }
};

function messageToWeb(message) {
    if (typeof message !== "string" || message.trim() === "") {
        return escapeHtml(message); // Возвращаем просто экранированный текст, если нет URL
    }

    // Ищем URL. Регулярка простая, но рабочая для большинства случаев
    const urlRegex = /(https?:\/\/[^\s"'<>()]+)/gi;
    const imageExtensions = /\.(png|jpg|jpeg|gif|webp|svg|bmp)$/i;
    const videoExtensions = /\.(mp4|webm|mov|avi|m4v)$/i;

    function escapeHtml(str) {
        if (typeof str !== "string") return "";
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function getCopyButton(url) {
        const safeUrl = escapeHtml(url);
        // Экранирование для вставки внутрь onclick="..."
        const jsSafeUrl = safeUrl.replace(/\\/g, "\\\\").replace(/'/g, "\\'");
        return `
            <button type="button"
                onclick="copyToClipboard('${jsSafeUrl}')"
                style="margin-top:4px; padding:4px 8px; font-size:11px; cursor:pointer; background:#f0f0f0; border:1px solid #ccc; border-radius:4px; vertical-align:middle;"
                title="Copy URL">
                🗎
            </button>
        `.trim();
    }

    // Если URL нет, просто возвращаем безопасный текст
    if (!urlRegex.test(message)) {
        return escapeHtml(message);
    }

    return message.replace(urlRegex, (match) => {
        const safeUrl = escapeHtml(match);

        if (imageExtensions.test(match)) {
            return `
                <div style="display:inline-block; margin:5px 0; vertical-align:top;">
                    <a href="${safeUrl}" target="_blank" rel="noopener noreferrer" style="display:block; max-width:500px;">
                        <img src="${safeUrl}" style="max-width:100%; height:auto; display:block; border-radius:4px;" alt="image" />
                    </a>
                    ${getCopyButton(match)}
                </div>
            `.trim();
        }

        if (videoExtensions.test(match)) {
            return `
                <div style="display:inline-block; margin:5px 0; vertical-align:top;">
                    <video controls playsinline style="max-width:500px; height:auto; display:block; border-radius:4px;">
                        <source src="${safeUrl}" type="video/mp4" />
                        Your browser does not support the video tag.
                    </video>
                    ${getCopyButton(match)}
                </div>
            `.trim();
        }

        // Обычная ссылка
        return `
            <div style="display:inline-block; margin:5px 0; vertical-align:top;">
                <a href="${safeUrl}" target="_blank" rel="noopener noreferrer" style="color:#007bff; text-decoration:underline; word-break:break-all;">
                    ${safeUrl}
                </a>
                ${getCopyButton(match)}
            </div>
        `.trim();
    });
}
