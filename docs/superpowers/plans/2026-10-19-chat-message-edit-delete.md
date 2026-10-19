# Chat Message Edit and Delete Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let owners, admins, and moderators edit or delete persisted chat messages through the existing composer while synchronizing changes to every connected client.

**Architecture:** Add chat-scoped storage mutations and explicit version 2 `edit_message`/`delete_message` WebSocket events. Vue alone decides which users see the controls; the socket validates shape and room ownership but intentionally performs no user or role authorization.

**Tech Stack:** PHP 8.2, Ratchet WebSocket, Illuminate Database query builder, Vue 3, native WebSocket, Node contract tests.

**Spec:** `docs/superpowers/specs/2026-10-19-chat-message-edit-delete-design.md`

## Global Constraints

- Authorization is UI-only; do not add application-user or role validation to the socket.
- Mutations must be scoped by both the joined `chat_id` and positive message ID.
- Editing changes only `message_text`; sender, timestamp, and client message ID remain unchanged.
- Deletion is permanent and requires native `window.confirm()` in Vue.
- Reuse `$t('i_edit')` and `$t('i_delete')`; do not add an icon package.
- Editing uses the existing main composer and highlights the target message gray.
- No database migration is required.
- Preserve unrelated changes in both repositories and do not create a Git commit without explicit user instruction.
- Do not run `npm run build:all` during implementation; after source verification, tell the user that the frontend must be rebuilt unless they separately authorize the build immediately beforehand.

## Review Focus

- A message ID from another joined or unjoined chat must not be edited or deleted.
- A deleted message being edited locally must leave its text as an ordinary composer draft.
- A send failure during editing must retain both edit mode and composer text for retry.
- Bot messages must expose controls only to frontend admins/moderators, never because they are treated as owned.
- Duplicate or out-of-order edit/delete broadcasts must be harmless when the target row is already updated or absent.

---

### Task 1: Chat-scoped storage mutations

**Files:**
- Modify: `server/srv/alina/Services/Chat/ChatStorage.php`
- Modify: `server/srv/alina/Services/Chat/DatabaseChatStorage.php`
- Modify: `server/srv/alina/mvc/Model/chat_message.php`
- Modify: `server/srv/alina/tests/support/ChatTestSupport.php`
- Modify: `server/srv/alina/tests/ChatStorageContractTest.php`
- Create: `server/srv/alina/tests/ChatMessageMutationStorageTest.php`

**Interfaces:**
- Produces: `ChatStorage::editMessage(int $chatId, int $messageId, string $text): array`.
- Produces: `ChatStorage::deleteMessage(int $chatId, int $messageId): bool`.
- `editMessage` returns the existing normalized stored-message shape and throws `RuntimeException('Message not found.')` when `(chat_id, id)` does not exist.
- `deleteMessage` returns `true` only when one matching row was deleted and `false` when no matching row exists.

- [ ] **Step 1: Write failing storage mutation tests**

Assert that editing changes only text, returns the complete original message metadata, persists in history, and cannot mutate the same ID through another chat. Assert deletion removes only the selected row from the selected chat, returns `false` for missing/cross-chat IDs, and leaves neighboring messages intact. Add both exact interface methods to every in-memory storage implementation.

- [ ] **Step 2: Run the storage test and verify RED**

Run: `php server/srv/alina/tests/ChatMessageMutationStorageTest.php`

Expected: FAIL because `editMessage` and `deleteMessage` do not exist.

- [ ] **Step 3: Implement model and storage mutations**

In `chat_message`, add chat-scoped update/read and delete methods using both `chat_id` and `id`. In `DatabaseChatStorage`, normalize the updated row with the existing `normalizeMessage`; throw the exact not-found exception for edit and return the delete boolean unchanged.

- [ ] **Step 4: Run storage contracts and verify GREEN**

Run:

```bash
php server/srv/alina/tests/ChatMessageMutationStorageTest.php
php server/srv/alina/tests/ChatStorageContractTest.php
```

Expected: both PASS.

- [ ] **Step 5: Checkpoint without committing**

Run PHP syntax checks on modified PHP files and `git diff --check`. Do not create a commit.

### Task 2: Version 2 edit/delete socket events

**Files:**
- Modify: `server/srv/alina/Services/Chat/ChatServer.php`
- Create: `server/srv/alina/tests/ChatMessageMutationProtocolTest.php`

**Interfaces:**
- Consumes Task 1 storage methods.
- Produces request types `edit_message` and `delete_message` on an admitted joined connection.
- Produces `message_edited` with the complete normalized protocol message under `message`.
- Produces `message_deleted` with string `messageId`.

- [ ] **Step 1: Write failing protocol tests**

Assert that `edit_message` trims and persists valid text, preserves sender/timestamp/client ID, and broadcasts a complete `message_edited` event to all admitted clients. Assert that `delete_message` permanently removes the row and broadcasts `message_deleted`. Cover non-positive/non-decimal IDs, empty text, text over 16,000 bytes, missing IDs, cross-chat IDs, pre-join and pending connections, storage exceptions followed by successful requests, and duplicate edit/delete arrival without worker failure.

- [ ] **Step 2: Run the protocol test and verify RED**

Run: `php server/srv/alina/tests/ChatMessageMutationProtocolTest.php`

Expected: FAIL with unknown event.

- [ ] **Step 3: Add event routing and validation**

Register both event types in the existing allowed event list and dispatch them only after current channel/admission validation. Add one private positive-message-ID parser shared by both handlers. Reuse the current 16,000-byte message limit and non-empty trimmed-text rule.

- [ ] **Step 4: Implement broadcasts**

For edit, call storage and pass the returned row through `protocolMessage` before broadcasting `message_edited`. For delete, treat `false` as `RuntimeException('Message not found.')`, then broadcast `message_deleted`. Do not inspect acting identity or role.

- [ ] **Step 5: Run all backend chat tests**

Run:

```bash
php server/srv/alina/tests/ChatMessageMutationProtocolTest.php
for test_file in server/srv/alina/tests/Chat*Test.php; do php "$test_file" || exit; done
```

Expected: all PASS.

- [ ] **Step 6: Checkpoint without committing**

Run PHP syntax checks and `git diff --check`. Do not create a commit.

### Task 3: Vue controls, composer edit mode, and synchronization

**Files:**
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatConnection.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/ChatMessage.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/index.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chat.css`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/locales/index.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-contract.mjs`

**Interfaces:**
- Produces: `createChatConnection(...).editMessage(messageId, text): boolean`.
- Produces: `createChatConnection(...).deleteMessage(messageId): boolean`.
- `ChatMessage` props add `can-manage: boolean` and `editing: boolean`; emits add `edit(message)` and `delete(message)`.
- Chat page state adds `editingMessageId: ref<string>`; empty string means normal composer mode.

- [ ] **Step 1: Extend the frontend contract and verify RED**

Add fake-socket assertions that transport methods send exact version 2 request shapes and return `false` on unavailable/send-failure sockets. Add SFC assertions for translated `$t('i_edit')`/`$t('i_delete')` controls, `canManage`, `editing`, gray target class, native confirmation, edit/cancel state, and incoming event handlers. Assert bots are not considered owned and management is `isOwn(message) || user.isAdmin() || user.isModerator()`.

Run: `node scripts/check-chat-contract.mjs`

Expected: FAIL because transport methods, props, events, and edit state do not exist.

- [ ] **Step 2: Implement transport methods**

Add `editMessage(messageId, text)` and `deleteMessage(messageId)` to the returned connection object using the existing private `send` function. Do not optimistically alter message state.

- [ ] **Step 3: Implement message footer controls**

Move the time into a footer directly below the message body and place compact edit/delete buttons beneath or beside it on the right. Render controls only for `canManage`; use translation keys for visible characters and accessible titles. Emit intentions without mutating the message. Apply a gray-background class when `editing` is true, using the established chat stylesheet only.

- [ ] **Step 4: Implement composer edit state**

On edit, copy `message.msg` into `draft`, set `editingMessageId`, focus the textarea, and keep the target highlighted. Add a compact translated cancel action near the composer using existing `i_cancel`. On submit, call `editMessage` when editing and retain state/text on failure; ordinary mode continues calling `sendMessage`. Clear edit mode only after successful send or explicit cancel.

- [ ] **Step 5: Apply incoming mutation events**

On `message_edited`, replace the matching array entry with the complete event message. On `message_deleted`, remove the matching row. If the deleted ID equals `editingMessageId`, clear only the edit target and preserve `draft` as normal unsent text. Unknown/duplicate IDs are no-ops.

- [ ] **Step 6: Implement UI-only visibility and confirmation**

Pass `canManage` as own-message-or-admin-or-moderator, explicitly excluding bot ownership through existing `isOwn` identity comparison. Before calling transport delete, require `window.confirm(t('Delete this message?'))`; add English/Russian confirmation copy while continuing to use existing icon keys.

- [ ] **Step 7: Run frontend checks and verify GREEN**

Run:

```bash
node scripts/check-chat-contract.mjs
npm run check:migration
git diff --check
```

Expected: all PASS.

- [ ] **Step 8: Final backend/frontend verification**

Run:

```bash
for test_file in server/srv/alina/tests/Chat*Test.php; do php "$test_file" || exit; done
git diff --check
git -C /home/qqq/_A001/rep/ALINA_FE diff --check
```

Expected: all PASS.

- [ ] **Step 9: Activation handoff**

Do not build by default. Tell the user to run `npm run build:all` in `/home/qqq/_A001/rep/ALINA_FE` unless they explicitly authorize the agent immediately before a build. Restart only the local `php82chat` container to activate backend socket changes when operational authorization is present. Do not perform a separate ALINA_BE deployment or create a commit.
