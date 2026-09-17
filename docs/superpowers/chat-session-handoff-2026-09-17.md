# Chat session handoff — 2026-09-17

## Attachment implementation update

User approved the simple existing-web-upload flow: ALINA-WEB stores files and returns relative paths; the sender adds `window.location.origin` and sends ordinary URL text through chat. Socket code and CLI database configuration are unchanged.

Implemented a multi-file paperclip picker in Chat using the existing AjaxAlina `/FileUpload` flow, authentication and watcher processing. Returned URLs are inserted into the draft (caption preserved); the user presses Send. Uploading blocks sending; errors preserve the draft. Upload results follow the originating channel if the user switches channels. Image/video previews continue to use native tags; audio previews now use native audio controls. Ordinary-user upload extensions now also include WAV, OGG/OGA, M4A, AAC, FLAC, MP4, WEBM, MOV and M4V. Existing storage, retention and image compression remain unchanged. Vite now proxies `/uploads` to the configured web API in development so window-origin URLs return actual files.

Verification: Vue SFC/CSS syntax and PHP lint passed; URL/audio classification checks passed; real browser web upload of a valid tiny PNG and WAV passed with caption and sender-origin URLs preserved, image/audio/video tags rendered, reconnect history available and no JS exceptions. Actual files were downloaded successfully through the dev origin; WAV bytes were unchanged. A malformed initial PNG fixture was rejected by existing image processing and correctly surfaced an upload error. Samples were uploaded under the Codex account and messages sent only in `codex-file-review`. No build, commit or container restart. User edits in `.vscode/bookmarks.json` and `server/srv/alina/cfg/db.php` were left intact.

The earlier proposal below is historical: drag/drop, paste, percentage progress, cancellation and automatic retention were not added to this first version.

## Resume here

User paused because of daily limits. Next topic is chat file attachments. User will decide how files should be stored and retained before implementation. No attachment implementation has started. Do not infer approval of a retention policy from the suggestions below.

## Projects and constraints

- Backend: `/home/qqq/_A001/rep/ALINA_BE`, PHP 8.2.
- Frontend: `/home/qqq/_A001/rep/ALINA_FE`, Vue 3.
- Never commit unless explicitly requested.
- Never run a frontend build without explicit permission immediately before the build.
- User authorized changes to both projects; tests are optional. Prefer focused verification.
- Do not touch unrelated containers, especially `bng*`. Only `alina_php82chat` was restarted for the previous history fix.
- Re-read current files and Git status before edits: the user has committed changes between tasks. Old `/tmp` staging copies may be stale.

## Completed work

- Vue chat view `src/views/Chat/index.vue`, reusable `src/components/Chat/` components, and lazy `/chat` route.
- Participant sidebar with avatars, nicknames, online/typing status; joins do not spam message history.
- Typed WebSocket protocol, public identity fields, server message IDs/timestamps, channel isolation, typing expiry, and legacy PHP chat compatibility.
- Safe rendering, reconnect handling, preserved drafts, responsive layout, and scroll/new-message handling.
- Server keeps the last 50 messages per room. When the room becomes empty, cleanup is delayed 120 seconds and cancelled on reconnect. This preserves history through refreshes and temporary disconnects. History remains in memory and is lost on worker restart.
- Chat hides the main menu/footer by default using the existing PageSettings mechanism from Chronometer. A button beside reconnect toggles visibility; leaving chat restores the prior setting. App reads/writes through the reactive PageSettings proxy.
- English/Russian labels added for the layout toggle.

## Relevant files

- BE: `server/srv/alina/Services/Chat/ChatServer.php` and its runner.
- BE legacy UI: `server/srv/alina/mvc/Controller/Chat.php`, `server/srv/alina/mvc/template/Chat/actionIndex.{php,js,css}`.
- FE: `src/components/Chat/{index.vue,chatConnection.js,chatProtocol.js,ChatMessage.vue,ChatParticipants.vue,chat.css}`.
- FE: `src/views/Chat/index.vue`, `src/App.vue`, `src/locales/index.js`, router and main menu links.
- History regression check: `server/srv/alina/tests/ChatHistoryReconnectTest.php`.
- Earlier design/plan: `docs/superpowers/specs/2026-09-17-text-chat-design.md`, `docs/superpowers/plans/2026-09-17-text-chat.md`.

## Verification already performed

- Focused PHP protocol and history checks passed.
- Live WebSocket check confirmed exactly the last 50 messages survived two empty-room reconnects.
- Vue SFC syntax checks passed without building the SPA.
- Actual headless-browser checks covered default hidden menu/footer, toggles, mobile layout, route leave/return, Ctrl+Alt+F synchronization, and no JavaScript errors.
- No builds or commits were made by Codex.

## Attachment proposal discussed, not implemented

- Upload files through HTTP; send attachment references/metadata through WebSocket.
- Paperclip button, drag-and-drop, screenshot paste, previews before sending, optional caption, progress/cancel/retry.
- Images displayed as thumbnails; other files as name/size/download cards.
- Include attachment metadata in message history so reconnect restores attachment messages.
- Preserve original bytes and generate thumbnails separately.
- Validate uploads on the server and check attachment ownership/access before accepting message references.
- OPEN: storage location, retention duration, relation to room-history cleanup, access policy, upload size/count limits, and supported file types. User will provide preferences next session.

## Existing uploader findings

- FE `src/Utils/AlinaCustomUploader.js`: CKEditor adapter uses `/FileUpload/CkEditor`, multipart upload, XHR progress/abort, `form_id=fileUpload`, `userfile[]`, and authentication/fingerprint headers.
- FE `src/components/elements/form/AlinaFileUploader.vue`: generic entity-bound uploader via AjaxAlina, multiple files, existing gallery display.
- BE `server/srv/alina/mvc/Controller/FileUpload.php`: authenticated upload processing, role/file-count watcher quotas, extension checks, metadata persistence and per-user directories.
- Ordinary-user supported extensions include common images, PDF, MP3, DOC/DOCX; privileged roles bypass extension restrictions. Confirm current settings before reuse.
- Registered-user watcher quota of 100 means file count, NOT 100 MB.
- Existing image processing shrinks images wider than 1500 pixels; do not blindly reuse it for original chat attachments.
- Existing entity binding and response formats mean a chat-specific association/reference flow is needed; this is not a drop-in integration.

## Local development helpers

- Dev chat URL previously verified: `https://localhost:8082/apps/vue/chat`; user-facing `https://zero.home:8082/apps/vue/chat?channel=1`.
- Backend default API URL: `https://zero.home:50443`; `/ws` proxies the chat worker.
- CUA was unavailable. Isolated headless Chrome plus Python websockets/CDP was used instead. The isolated browser was closed after verification.
- Temporary scripts under `/tmp/alina-chat/` include Vue syntax, protocol, live history, browser, and toggle checks. They may not persist forever.
- A normal registered Codex account was created with watcher rules respected. Credentials are in `/tmp/alina-chat/account.json` (mode 0600); do not print secrets or copy them into documentation. Browser session is currently closed.
- User previously authorized chatting on-site; that activity ended when both returned here. Do not resume posting unless asked.

## Next session

Read this handoff and the user's storage/retention preferences, inspect current uploader/chat files, then agree on attachment behavior before implementing. No need to revisit completed chat work unless a new issue is reported.
