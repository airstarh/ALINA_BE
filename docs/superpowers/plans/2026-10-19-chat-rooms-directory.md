# Chat Rooms Directory Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `/#/chat/rooms`, backed exclusively by the WebSocket chat service, with origin-scoped paginatable lists of rooms the current identity has and has not participated in.

**Architecture:** A pre-join version 2 `list_rooms` WebSocket request carries the existing public user identity and independent pagination for both lists. `DatabaseChatStorage` queries only `chat` and `chat_message`; Vue renders the returned read model in two tables without touching the main application database for chat data.

**Tech Stack:** PHP 8.2, Ratchet WebSocket, Illuminate Database/Capsule query builder, Vue 3, Vue Router 4, native WebSocket, Node contract tests.

**Spec:** `docs/superpowers/specs/2026-10-19-chat-rooms-directory-design.md`

## Global Constraints

- Restrict every room-directory query to the normalized WebSocket handshake origin.
- Derive participation only from retained non-bot `chat_message` rows; deleted kicked-user messages no longer count.
- Registered identities use `sender_type = user`; anonymous identities use the persistent `guestId` and `sender_type = guest`.
- Return independent `limit`, `offset`, and `total` metadata for `mine` and `others`; clamp each limit to `1..1000`.
- Order both lists by latest message descending, empty rooms last, then chat ID for deterministic ties.
- Viewing the directory must not create or join a room or affect presence.
- Do not add a table or index without separate evidence and approval.
- The user explicitly authorized one post-implementation `npm run build:all` in `/home/qqq/_A001/rep/ALINA_FE` for this feature.
- The user explicitly authorized restarting the local Docker containers after this feature is implemented and verified.
- Do not execute migrations, deploy to another host, or create a Git commit unless the user explicitly requests that operation.

## Review Focus

- A valid registered user payload that also carries a stale guest ID must classify participation only as the registered user.
- A malformed/missing persistent guest ID must receive a protocol error rather than silently use the connection resource ID.
- Concurrent reconnects must not let a stale socket response replace rows from the current connection.
- Multibyte names and identical latest-message timestamps must retain deterministic ordering and safe rendering.
- Independent pagination must allow one list to change offset without changing or truncating the other list.

---

### Task 1: Room-directory storage contract and database read model

**Files:**
- Modify: `server/srv/alina/Services/Chat/ChatStorage.php`
- Modify: `server/srv/alina/Services/Chat/DatabaseChatStorage.php`
- Modify: `server/srv/alina/mvc/Model/chat.php`
- Modify: `server/srv/alina/tests/support/ChatTestSupport.php`
- Modify: `server/srv/alina/tests/ChatStorageContractTest.php`
- Create: `server/srv/alina/tests/ChatRoomDirectoryStorageTest.php`

**Interfaces:**
- Produces: `ChatStorage::listRooms(string $origin, string $senderType, string $senderId, array $pagination): array`.
- Produces result: `['mine' => ['rows' => list<room>, 'total' => int, 'limit' => int, 'offset' => int], 'others' => same]`.
- Produces room: `['id' => int, 'channel' => string, 'chatName' => string, 'participantCount' => int, 'latestMessageAt' => ?int]`.
- Pagination input: `['mine' => ['limit' => int, 'offset' => int], 'others' => ['limit' => int, 'offset' => int]]`; values reaching storage are already validated.

- [ ] **Step 1: Write the failing storage contract tests**

Create fixtures spanning two origins, registered and guest senders, duplicate messages by one sender, a bot-only room, an empty room, tied timestamps, and deleted participant messages. Assert:

- origin isolation;
- `mine`/`others` partitioning for both registered and guest identities;
- distinct non-bot participant counts;
- latest-message descending order, empty rooms last, ID tie-breaker;
- independent limits and offsets;
- totals count all matching rooms rather than only returned rows.

Add direct interface coverage to `ChatStorageContractTest.php` so every in-memory implementation must implement `listRooms`.

- [ ] **Step 2: Run the new test and verify RED**

Run: `php server/srv/alina/tests/ChatRoomDirectoryStorageTest.php`

Expected: FAIL because `ChatStorage::listRooms` and its implementations do not exist.

- [ ] **Step 3: Add the storage interface and in-memory test implementation**

Implement the exact interface above in `ChatStorage.php` and the test storage in `tests/support/ChatTestSupport.php`. Keep the in-memory implementation behaviorally independent from the database query so expected literals—not production SQL—drive the contract.

- [ ] **Step 4: Implement the database read model**

Add focused query methods to `mvc/Model/chat.php` for paged rows and matching counts, consumed by `DatabaseChatStorage::listRooms`.

Use grouped `chat` plus left-joined `chat_message` aggregates for row data and correlated `EXISTS`/`NOT EXISTS` for identity partitioning. Count distinct `(sender_type, sender_id)` while excluding `bot`. Select `MAX(created_at)` for `latestMessageAt`; order non-null maxima first, descending timestamp second, and chat ID third.

- [ ] **Step 5: Run storage tests and verify GREEN**

Run:

```bash
php server/srv/alina/tests/ChatRoomDirectoryStorageTest.php
php server/srv/alina/tests/ChatStorageContractTest.php
```

Expected: both PASS.

- [ ] **Step 6: Inspect the local query plan without changing data**

Run an `EXPLAIN` equivalent for registered `mine` and `others` queries against the local `zero` schema. Record whether existing `chat(origin, channel_name_pseudo_id)` and `chat_message(chat_id, client_message_id)` indexes avoid pathological full scans. Do not add an index in this task; report evidence if one is warranted.

- [ ] **Step 7: Checkpoint without committing**

Run `git diff --check`. Do not create a commit without explicit user authorization.

### Task 2: Pre-join WebSocket directory protocol

**Files:**
- Modify: `server/srv/alina/Services/Chat/ChatServer.php`
- Create: `server/srv/alina/tests/ChatRoomDirectoryProtocolTest.php`

**Interfaces:**
- Consumes: `ChatStorage::listRooms(...)` from Task 1.
- Produces request type: `list_rooms` without a `channel`.
- Produces response type: `rooms` containing the Task 1 result under `mine` and `others`.

- [ ] **Step 1: Write the failing protocol tests**

Assert that `list_rooms`:

- works before joining any channel and does not call `openChat`;
- uses normalized handshake origin;
- chooses `user:<id>` over a stale guest ID;
- accepts a valid persistent guest UUID;
- rejects missing/invalid guest identity;
- validates independent integer pagination, rejects negative offsets, and clamps limits above 1,000;
- returns the exact version 2 `rooms` shape;
- turns storage exceptions into an error event while a subsequent request still succeeds;
- leaves channel membership and presence unchanged.

- [ ] **Step 2: Run protocol test and verify RED**

Run: `php server/srv/alina/tests/ChatRoomDirectoryProtocolTest.php`

Expected: FAIL with unknown event or missing response.

- [ ] **Step 3: Route `list_rooms` before channel validation**

In `ChatServer::onMessage`, recognize `list_rooms` after JSON/version validation but before the existing mandatory-channel and joined-room path. Keep all current room events on their existing validation path.

- [ ] **Step 4: Implement directory identity and pagination validation**

Add focused private methods that produce `(senderType, senderId)` only for a positive registered ID or valid persistent guest UUID, and normalized independent pagination with maximum limit 1,000. Do not fall back to `resourceId` for directory participation.

- [ ] **Step 5: Call storage and send the response**

Call `listRooms` with the connection's normalized origin. Return `['version' => 2, 'type' => 'rooms', 'mine' => ..., 'others' => ...]`. Reuse the existing exception boundary so storage failures remain non-fatal.

- [ ] **Step 6: Run protocol and existing chat tests**

Run:

```bash
php server/srv/alina/tests/ChatRoomDirectoryProtocolTest.php
for test_file in server/srv/alina/tests/Chat*Test.php; do php "$test_file" || exit; done
```

Expected: all PASS.

- [ ] **Step 7: Checkpoint without committing**

Run PHP syntax checks on modified files and `git diff --check`. Do not create a commit.

### Task 3: Frontend identity refresh and room-directory transport

**Files:**
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatIdentity.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/index.vue`
- Create: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatRoomsConnection.js`
- Create: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-rooms-contract.mjs`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/package.json`

**Interfaces:**
- Produces: `refreshChatIdentity(user, apiBaseUrl, signal): Promise<void>` shared by room and directory pages.
- Produces: `createChatRoomsConnection({url, getUser, getPagination, onRooms, onStatus, WebSocketClass})` with `start()`, `stop()`, and `retry()`.
- Emits status objects with states `connecting`, `loading`, `connected`, `reconnecting`, and `error`.

- [ ] **Step 1: Write failing identity and transport tests**

In the new Node contract script, use Vue `reactive` and a fake WebSocket to assert:

- identity refresh applies the complete `CurrentUser` response and forwards credentials/ALINA headers;
- socket open sends one `list_rooms` request with sanitized public identity and independent pagination;
- a matching `rooms` response reaches `onRooms`;
- malformed/wrong-version messages are ignored;
- error and close transitions are reported;
- retry creates a replacement connection;
- a response from a stopped/replaced socket is ignored;
- `stop()` clears timers and closes handlers.

- [ ] **Step 2: Run the new frontend contract and verify RED**

Run: `node scripts/check-chat-rooms-contract.mjs`

Expected: FAIL because the shared refresh function and directory transport do not exist.

- [ ] **Step 3: Extract shared identity refresh**

Move the existing `/root/status` refresh behavior from `Chat/index.vue` into `refreshChatIdentity`. Preserve credentials, ALINA headers, optional `uid`/`token`, abort handling, and `CurrentUser.applyAttributes`. Update the existing chat page to use it without changing chat behavior.

- [ ] **Step 4: Implement `createChatRoomsConnection`**

Keep connection ownership, generation/stale-event protection, retry timer cleanup, request serialization, and response filtering inside this framework-independent module. Do not join a room or send presence events.

- [ ] **Step 5: Register the contract command**

Add `check:chat-rooms` to `package.json`; do not add it to a build command. Include it in the existing non-build migration/check chain only if that chain remains semantically accurate.

- [ ] **Step 6: Run frontend transport checks**

Run:

```bash
node scripts/check-chat-rooms-contract.mjs
node scripts/check-chat-contract.mjs
```

Expected: both PASS.

- [ ] **Step 7: Checkpoint without committing**

Run `git -C /home/qqq/_A001/rep/ALINA_FE diff --check`. Do not build or commit.

### Task 4: `/chat/rooms` route and directory page

**Files:**
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/router.js`
- Create: `/home/qqq/_A001/rep/ALINA_FE/src/views/Chat/rooms.vue`
- Create: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/ChatRoomsTable.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/locales/index.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-rooms-contract.mjs`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-vite-contract.mjs`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/vite.config.js`

**Interfaces:**
- Consumes: `refreshChatIdentity` and `createChatRoomsConnection` from Task 3.
- Consumes response sections shaped by Task 1.
- Produces route `/chat/rooms` and reusable `ChatRoomsTable` props `{title, section}`.

- [ ] **Step 1: Extend the failing frontend contract for route and rendering**

Assert:

- router contains `/chat/rooms` mapped to the new page;
- page renders separate loading, retry/error, and two-list states;
- each table exposes order, human name, participants, and latest-message columns;
- links use `/#/chat?channel=<encoded>` semantics through router resolution, `target="_blank"`, and `rel="noopener"`;
- row numbering starts at `offset + 1`;
- empty dates render empty;
- totals sum only displayed participant counts and select the newest displayed timestamp;
- empty sections still render their totals row;
- headings show displayed row counts;
- multibyte names remain text bindings, not HTML injection;
- changing one section's pagination input leaves the other section's values intact.

- [ ] **Step 2: Run frontend contract and verify RED**

Run: `node scripts/check-chat-rooms-contract.mjs`

Expected: FAIL because the route and components do not exist.

- [ ] **Step 3: Add the route and page state orchestration**

Register `/chat/rooms` before the generic chat route for clarity. In `rooms.vue`, refresh identity, start the directory transport with both sections at `{limit: 1000, offset: 0}`, ignore stale async work after unmount, and expose manual retry.

- [ ] **Step 4: Implement `ChatRoomsTable.vue`**

Render semantic responsive tables and the agreed totals row using the existing `table-responsive` and `table table-dark table-striped table-hover table-sm` classes. Format millisecond timestamps through `UtilsDate.dateObjFormat(new Date(timestamp), 'yyyy-MM-dd HH:mm')`, returning an empty string for `null`. Build room links with Vue Router resolution and encoded query values.

- [ ] **Step 5: Apply existing styling and add translations**

Use only existing ALINA/Bootstrap container, spacing, table, alert, button, text, and font-weight classes. Do not create a chat-room stylesheet or add CSS rules. Add English/Russian strings for headings, columns, loading, empty, retry, and error states. Do not alter existing chat layout styles.

- [ ] **Step 5a: Keep generated chunks compatible with existing Nginx protection rules**

Set Vite `chunkFileNames` to `[hash].js` and cover it in `check-vite-contract.mjs`. Dependency-derived names such as `runtime-core.esm-bundler.<hash>.js` collide with the existing Nginx `/run` protected-path expression and receive HTTP 403. Do not change Nginx for this feature.

- [ ] **Step 6: Run all non-build frontend checks**

Run:

```bash
npm run check:chat-rooms
npm run check:migration
```

Expected: all PASS.

- [ ] **Step 7: Run final repository verification**

Run:

```bash
for test_file in server/srv/alina/tests/Chat*Test.php; do php "$test_file" || exit; done
git diff --check
git -C /home/qqq/_A001/rep/ALINA_FE diff --check
```

Expected: all tests and checks PASS.

- [ ] **Step 8: Build the approved frontend targets**

Run: `npm run build:all` in `/home/qqq/_A001/rep/ALINA_FE`.

Expected: both `build:vov` and `build:zero` complete successfully.

- [ ] **Step 9: Reload the local WebSocket worker**

ALINA_BE needs no local deployment step because the repository is the served tree. Restart only the long-running socket worker after backend socket changes:

```bash
docker compose -f dc.all.yml -f dc.all.php82.yml -f dc.dev.yml restart php82chat
```

Expected: `php82chat` restarts successfully; verify the worker is healthy and the local `/ws` endpoint upgrades with HTTP 101. Ordinary PHP HTTP changes need only a repeated request.

- [ ] **Step 10: Deployment handoff**

Report changed files, test/build evidence, local restart evidence, and any query-plan concern. Do not deploy to another host or create a commit unless explicitly requested.

## Final implementation notes

- The first generated build exposed a blank page because `runtime-core.esm-bundler.<hash>.js` received HTTP 403 from the existing Nginx protected-path regex. The accepted solution keeps Nginx unchanged and emits hash-only chunk filenames from Vite.
- The first room-directory presentation used custom CSS and locale-dependent dates. The accepted UI removes that stylesheet, reuses established ALINA/Bootstrap classes, and formats dates as `yyyy-MM-dd HH:mm`.
- The frontend source changes require `npm run build:all`; ALINA_BE has no separate local deployment step. Restart `php82chat` only when its long-running PHP socket code changes.
