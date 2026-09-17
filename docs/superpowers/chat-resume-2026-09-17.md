# Resume chat work — latest checkpoint, 2026-09-17

Read this first next session, then the earlier `chat-session-handoff-2026-09-17.md`, `chat-browser-identity.md`, and `keendns-chat-diagnostics.md` as needed.

User paused to run additional tests. Latest unresolved issue is persistent guest identity activation, described below. No further implementation is requested until user returns with feedback.

## Constraints

- Never commit without explicit user request.
- Never build the Vue SPA without explicit permission immediately before building.
- Make edits only in local projects. User deploys and restarts the remote stack.
- Remote host may be inspected read-only via `ssh bbb` or `/mnt/sshfs/bbb/mnt/d1001/_docker/az/ALINA_BE/`.
- Do not modify/restart unrelated containers, especially bng containers.
- Re-read current files and Git status before editing; user has committed changes between tasks. Temporary staging directories can be stale.

## Latest user observation / unresolved issue

Avatar links for both participants resolve to the same room and work as desired. However user sees `AAA (Guest 1145)` while an opened room URL is `https://localhost:8082/apps/vue/chat?channel=1_guest_1139`.

These numeric identifiers LOOK LIKE legacy socket resource IDs. The intended new implementation uses a persisted browser UUID, displays its first eight characters, and uses the full UUID in room names. A reconnect changing the legacy socket ID could explain 1139 versus 1145. We told the user this suggests the running worker still has old code or is not receiving the UUID, but **this hypothesis has not been verified against live protocol payloads**. Do not assert root cause without inspecting the join payload, returned presence/message fields, running worker mount/code, and singleton guestId.

Next investigation should check frontend `CurrentUser.attributes.guestId`, browser storage `alina.chat.guestId`, outbound join CurrentUser.guestId, inbound CurrentUser.guestId and participantId. A valid new anonymous participantId should be `guest:<full UUID>`. If worker code is stale, user must activate the local backend change. Worker restart loses in-memory history; do not restart it autonomously.

## Exact latest display requirement

- Default: `Guest <short ID>`.
- Renamed: `New Name (Guest <short ID>)`.
- User explicitly corrected an intermediate misunderstanding where we changed renamed labels to `New Name (<short ID>)`; that intermediate format is now reverted.
- Shared frontend displayName receives participantId as fallback when guestId is absent. This makes older-worker data display `AAA (Guest 1145)` rather than only AAA. It does NOT make socket IDs persistent or repair the underlying old-worker identity problem.
- Applied to participant list, message headers and typing summary. Historical messages retain the nickname captured when they were sent; no retrospective nickname rewrite has been implemented.

## Persistent browser identity and room links implemented locally

FE:
- `src/services/GuestIdentity.js`: crypto.randomUUID(), stored under `alina.chat.guestId`; existing nickname storage key retained; default nickname filled into input; blocked storage falls back to page memory.
- `src/services/CurrentUser.js`: guests use id=0 and separate guestId; applying web responses preserves browser UUID, registered users omit guestId, logout restores it.
- `src/components/Chat/index.vue`: guest input, 25-character limit, saved nickname, cross-tab storage-event synchronization, existing join update after 400ms debounce.
- `src/components/Chat/chatProtocol.js`: public payload includes guestId; consistent display format; canonical privateChannel helper.
- `src/components/Chat/ChatParticipants.vue`: other people's avatars link to /chat resolved with router deployment base and query channel; new tab, own avatar has no link.
- `src/components/Chat/ChatMessage.vue`: stable guest label/fallback in message header.
- `src/locales/index.js`: link labels en/ru.

Channel ordering: two registered IDs numeric ascending; registered ID before guest; two guest UUIDs lexicographic ascending. Full UUID in channel, short prefix only for display. Legacy numeric guest participant IDs accepted as compatibility fallback. Room links are separate rooms, NOT authorization-restricted conversations. Identity claims are not credentials. Storage is scoped to website origin and browser profile; different domains/profiles or clearing storage create different identities.

BE `server/srv/alina/Services/Chat/ChatServer.php`: validates v4 guest UUID, includes it in public user, uses stable guest participantId and deduplicates guest tabs in presence, formats renamed label with Guest prefix. Old clients without guestId keep socket-number fallback. No database/authentication changes to CLI worker.

Checks passed: canonical channel and names, guest storage across module/tab instances, singleton login/logout semantics, PHP UUID validation/default/renamed/legacy labels, guest multi-tab presence and stable message identity, existing last-50 history reconnect checks, Vue SFC/CSS syntax, diff whitespace. These are focused checks, **not an end-to-end browser deployment validation of the new persistent guest identity**.

PHP regression check: `server/srv/alina/tests/ChatGuestIdentityTest.php`. Temporary checks under `/tmp/alina-chat-identity/`, latest UI label test `/tmp/alina-chat-name-fix/check.mjs`. Old tests expecting the intermediate suffix format may need updating before reuse. Vue syntax helper `/tmp/alina-chat/check-vue.cjs` does not build the SPA.

## Other completed chat features

- Last 50 room messages preserved across reconnects; empty rooms retained 120 seconds; in-memory history lost on worker restart.
- Shared PageSettings hides main menu/footer by default in chat; toggle beside reconnect; prior setting restored on leaving.
- Multi-file paperclip upload via existing ALINA web FileUpload/AjaxAlina/auth/watcher flow; URLs use sender window.location.origin, inserted into draft then sent as ordinary chat text. Socket uploads nothing. Vite proxies /uploads to web backend for dev-origin links.
- Native image/video/audio previews; backend common audio/video upload extensions expanded. Existing storage/retention/image resizing unchanged.
- Fenced code blocks render escaped text in pre/code; optional language ignored, no highlighting. User CSS: white-space pre, overflow auto, black background, #70ff70 text, monospace; no wrapping, constrained width. URLs inside code remain literal.
- Chat UI/transport features detailed in earlier handoff remain in place.

## Nginx/KeenDNS investigation

Public site `https://azo.zadobro.crazedns.ru/apps/vue/chat`, backend bbb 192.168.1.120:50443, KeenDNS cloud access. User deployed previous changes and restarted stack.

Local nginx changes: /ws inactivity timeouts 3600s, TCP upstream keepalive, buffering/cache off; chat handshake/timing log; ordinary alina.timing.log; PHP-independent /_alina/health. Concrete deployed startup failure found: host not found in upstream php82chat. Latest patch in shared location uses Docker resolver 127.0.0.11 valid10s timeout5s and variable destinations for chat/signaling/PHP, to avoid config-load backend resolution and refresh container addresses. Local nginx -t passed; no runtime activation/restarts performed by us.

Earlier logs showed many 30-second upgraded WebSocket sessions despite hour timeout. Firefox sometimes reconnects after being on another virtual desktop; public health remained responsive and Firefox reload restored messages while Chrome remained connected. User is continuing observation. Whole-page timeout root cause remains unconfirmed; do not assume Firefox bug or nginx overload. Diagnostics notes have measured probes and log details.

## Resume procedure

Read user's new feedback, inspect local working trees, verify actual UUID protocol path before fixing the mismatch, and preserve the exact `Nickname (Guest <id>)` UI format. No builds/commits/service activation unless explicitly requested.
