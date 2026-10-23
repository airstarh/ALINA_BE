# Private Chat Title Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give each newly created private chat a deterministic `PRIVATE: Name 1 & Name 2` human title without changing its channel ID or URL.

**Architecture:** ALINA_FE builds the proposed title from the two displayed names using the same canonical identity ordering as the private channel, carries it in Vue Router history state without changing the URL, and includes it separately in the socket join payload. ALINA_BE validates the channel/title pair and supplies the title to chat storage only as an insert default; storage never applies it when reopening an existing room.

**Tech Stack:** Vue 3, Vue Router history state, JavaScript chat protocol tests, PHP 8.5, Ratchet, Illuminate database query builder, dependency-free PHP chat tests.

**Spec:** `docs/superpowers/specs/2026-10-23-private-chat-title-design.md`

## Global Constraints

- `channel_name_pseudo_id` and every private-chat URL remain unchanged.
- Only initial creation may generate `chat_name_human`; reopening never overwrites it.
- Later manual renaming continues to modify only `chat_name_human`.
- Support user/user, user/guest, and guest/guest channels with complete displayed names in canonical channel-ID order.
- The proposed title travels in router history state and the WebSocket payload, never in the URL.
- If the proposed title is missing, malformed, or over 200 Unicode characters, retain the technical channel ID as the initial human name.
- No database migration or Git commit is required.
- Do not run `npm run build:all` without the user's immediate explicit permission; do not restart containers without explicit authorization.

## Review Focus

- Numeric user IDs such as `2` and `10` must retain the canonical numeric ordering already used by Vue.
- UUID guest IDs containing hyphens must parse without treating arbitrary underscore-containing channels as private.
- A numeric legacy guest ID must remain distinguishable from a registered user wherever its explicit `guest_` token is present.
- Router reload/back behavior must not append title data to the URL or reuse a proposal for the wrong channel.
- A concurrent second opener must read the first inserted title and must not replace it.

---

### Task 1: Frontend canonical private title and navigation state

**Files:**
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatProtocol.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/ChatParticipants.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/ChatMessage.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/index.vue`
- Test: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatProtocol.test.mjs`

**Interfaces:**
- Produces: `privateChat(firstId, firstName, secondId, secondName): {channel: string, name: string}|null`, ordering identity/name pairs exactly as `privateChannel()` does.
- Produces: route navigation state shaped as `{privateChatName: string, privateChatChannel: string}` while the visible route remains `/#/chat?channel=<channel>`.
- Produces: optional `chatName` supplied to the chat connection only when history state belongs to the current channel.

- [ ] **Step 1: Write failing frontend protocol tests**

Assert exact channel and title values for user/user, user/guest, guest/user, guest/guest, numeric user ordering (`2` before `10`), complete displayed guest names, empty names, equal IDs, and titles over 200 Unicode characters.

- [ ] **Step 2: Run the frontend protocol test and verify RED**

Run:

```bash
cd /home/qqq/_A001/rep/ALINA_FE && node --test src/components/Chat/chatProtocol.test.mjs
```

Expected: failure because `privateChat()` does not exist.

- [ ] **Step 3: Implement canonical pair generation**

Refactor the existing private-channel ordering into one internal pair sorter, retain `privateChannel()` behavior, and add `privateChat()` so each name remains attached to its identity during sorting.

- [ ] **Step 4: Carry the proposal through navigation without changing the URL**

Use Vue Router navigation state for participant-avatar and message PRIVATE links. Read the state in the chat view/component only when `privateChatChannel` equals the current channel, then provide `privateChatName` to the connection join payload. Preserve ordinary anchor semantics and the existing visible `channel` query.

- [ ] **Step 5: Verify frontend tests GREEN**

Run:

```bash
cd /home/qqq/_A001/rep/ALINA_FE && node --test src/components/Chat/chatProtocol.test.mjs
```

Expected: `PASS` for existing private channels and new title behavior. Do not run an SPA build.

---

### Task 2: Socket validation and creation-only title storage

**Files:**
- Modify: `server/srv/alina/Services/Chat/ChatStorage.php`
- Modify: `server/srv/alina/Services/Chat/DatabaseChatStorage.php`
- Modify: `server/srv/alina/mvc/Model/chat.php`
- Modify: `server/srv/alina/tests/support/ChatTestSupport.php`
- Modify: `server/srv/alina/tests/ChatStorageContractTest.php`
- Modify: `server/srv/alina/Services/Chat/ChatServer.php`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatConnection.js`
- Test: `server/srv/alina/tests/ChatManagementTest.php`

**Interfaces:**
- Consumes: optional `chatName` in the version-2 socket join payload.
- Changes: `ChatStorage::openChat(string $origin, string $channel, ?string $initialName = null): array`.
- Changes: `chat::insertForChannel(string $origin, string $channel, string $name): stdClass`.

- [ ] **Step 1: Write failing storage-contract tests**

Extend the memory contract tests to assert that an explicit initial name is stored on first creation, reopening with a different initial name preserves the original value, an omitted initial name retains the channel fallback, and a later `renameChat()` remains preserved on subsequent opens.

- [ ] **Step 2: Write failing socket protocol tests**

Assert a valid private channel plus `PRIVATE: Alice & Bob` stores and broadcasts that initial name. Assert ordinary channels, missing `PRIVATE:` prefix, blank values, non-string values, over-200-character values, and malformed private channel IDs fall back to the technical channel name. Assert a later join proposal never overwrites an existing or manually renamed title.

- [ ] **Step 3: Run storage tests and verify RED**

Run:

```bash
docker exec alina_php82 php /srv/alina/tests/ChatStorageContractTest.php
```

Expected: failure because the new initial-name behavior does not exist.

- [ ] **Step 4: Implement creation-only naming**

Update `openChat()` implementations so `initialName` is used only by the insert branch and defaults to the technical channel. Pass the selected name into `chat::insertForChannel()`. Preserve the existing duplicate-insert recovery path so a concurrent opener reads the already-created row without renaming it.

- [ ] **Step 5: Implement socket validation and frontend payload**

Add optional `chatName` to the frontend version-2 join message. In `ChatServer::join()`, accept it only when the channel exactly matches the established canonical private-channel forms and the trimmed name starts with `PRIVATE:`, is non-empty after the prefix, and is at most 200 Unicode characters. Pass the validated proposal to `openChat()`; otherwise pass `null`.

- [ ] **Step 6: Run storage tests and verify GREEN**

Run:

```bash
docker exec alina_php82 php /srv/alina/tests/ChatStorageContractTest.php
```

Expected: `PASS` including creation-only naming and origin isolation.

---

### Task 3: Integrated private-title behavior

**Files:**
- Modify: `server/srv/alina/tests/ChatManagementTest.php`
- Modify: `server/srv/alina/tests/support/ChatTestSupport.php`

**Interfaces:**
- Consumes: frontend-generated `chatName` and the optional `initialName` argument to `openChat()`.
- Produces: creation-time private titles through the existing `chat_state` event; no protocol field or route change.

- [ ] **Step 1: Write failing private-title protocol tests**

Join user/user, user/guest, and guest/guest canonical private channels with literal proposed names. Assert the first `chat_state.chat.name` exactly matches each valid proposal.

- [ ] **Step 2: Add live-name and fallback cases**

Assert a missing or invalid proposal leaves the technical channel name and ordinary non-private channels retain their channel-name default.

- [ ] **Step 3: Add preservation and concurrency cases**

Assert reopening an existing private room does not replace its initial or manually renamed title, and a simulated duplicate-insert recovery returns the stored winner without applying the second proposed title.

- [ ] **Step 4: Run the protocol test and verify RED**

Run:

```bash
docker exec alina_php82 php /srv/alina/tests/ChatManagementTest.php
```

Expected: failure because joining a new private channel still stores its technical channel as the name.

- [ ] **Step 5: Complete creation-time integration**

Complete any protocol/storage integration needed for the valid proposal to reach only the insert path. Leave admission, routes, and later rename behavior unchanged.

- [ ] **Step 6: Run the protocol test and verify GREEN**

Run:

```bash
docker exec alina_php82 php /srv/alina/tests/ChatManagementTest.php
```

Expected: `PASS` for private naming and existing management behavior.

---

### Task 4: Full backend verification and documentation

**Files:**
- Modify: `docs/superpowers/chat-current-state.md`
- Modify: `docs/superpowers/alina-conversation-current-state.md`

**Interfaces:**
- Consumes: completed private-title behavior from Tasks 1–3.
- Produces: durable project context describing creation-only private titles and unchanged channel links.

- [ ] **Step 1: Update current-state documentation**

Record the exact title format, frontend proposal/socket validation flow, canonical identity ordering, user/guest support, creation-only rule, fallback, and invariant that renaming never changes `channel_name_pseudo_id` or the URL.

- [ ] **Step 2: Run all directly runnable backend tests**

Run every `server/srv/alina/tests/*Test.php` inside `alina_php82`, excluding only the known legacy `Alina001Test.php` bootstrap test.

Expected: every executed test prints `PASS` and exits zero.

- [ ] **Step 3: Run static verification**

Run:

```bash
docker exec alina_php82 sh -lc 'find /srv/alina -path /srv/alina/vendor -prune -o -name "*.php" -type f -print0 | xargs -0 -n1 php -l'
git diff --check
```

Expected: no PHP syntax errors and no whitespace errors.

- [ ] **Step 4: Report activation requirement**

Ask immediately before running the required ALINA_FE build. Do not restart containers automatically. Report that activation requires:

```bash
cd /home/qqq/_A001/rep/ALINA_FE && npm run build:all
docker restart alina_php82chat
```

before manual browser verification of newly created private chats.
