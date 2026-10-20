# Chat Message Replies Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add one-level, persisted chat-message replies with a server-authored 150-character quote and frontend-only orange parent selection.

**Architecture:** Vue sends a dedicated `reply_message` command containing the parent ID and ordinary draft fields. Chat storage resolves the parent and atomically/idempotently stores one canonical reply message; Vue safely parses only that canonical prefix into structured quote UI without general HTML rendering.

**Tech Stack:** PHP 8.2, Ratchet WebSocket server, Illuminate Database/Capsule, Vue 3 Composition API, project Node contract scripts, CSS.

**Spec:** `docs/superpowers/specs/2026-10-20-chat-message-replies-design.md`

## Global Constraints

- Do not create a Git commit unless the user explicitly requests one.
- Do not run a JavaScript SPA build; the user performs `npm run build:all` separately.
- Preserve unrelated working-tree changes in ALINA_BE and ALINA_FE.
- Do not add a database migration or relation for replies.
- Store exactly one message per reply; Robocop is not involved.
- Quote only the first 150 Unicode characters of the parent's own text.
- Do not use `v-html`; quote fields render as escaped text.
- Ordinary messages and edits must not be able to forge the reserved canonical reply prefix.

## Review Focus

- A retry after the parent is deleted must still resolve the already-stored reply by `clientMessageId`, not fail or duplicate it; Task 1 tests this ordering.
- A parent from another chat must be rejected without storing a reply; Task 1 tests chat scoping.
- Parent text containing HTML metacharacters or a Unicode character at the 150-character boundary must remain safe and correctly bounded; Task 1 tests both.
- A failed socket send must preserve the selected parent and composer draft for retry; Task 3 tests transport failure behavior.
- Reply parsing must leave malformed or user-authored lookalike markup as ordinary escaped text; Task 3 tests strict canonical parsing.

---

### Task 1: Canonical Reply Composition and Atomic Persistence

**Files:**
- Create: `server/srv/alina/Services/Chat/ChatReply.php`
- Create: `server/srv/alina/tests/ChatReplyStorageTest.php`
- Modify: `server/srv/alina/Services/Chat/ChatStorage.php`
- Modify: `server/srv/alina/Services/Chat/DatabaseChatStorage.php`
- Modify: `server/srv/alina/mvc/Model/chat_message.php`
- Modify: `server/srv/alina/tests/support/ChatTestSupport.php`
- Modify: `server/srv/alina/tests/ChatStorageContractTest.php`

**Interfaces:**
- Produces: `ChatReply::hasCanonicalPrefix(string $text): bool`.
- Produces: `ChatReply::ownText(string $text): string` removing only a valid canonical prefix at offset zero.
- Produces: `ChatReply::replaceOwnText(string $existingText, string $newOwnText): string` preserving an existing canonical prefix byte-for-byte.
- Produces: `ChatReply::compose(array $parent, string $replyText): string` with escaped author/excerpt, numeric parent timestamp, a 150-Unicode-character excerpt, newline, and reply body.
- Produces: `ChatStorage::appendReply(int $chatId, int $parentMessageId, array $message): array{message: array, inserted: bool}`.
- Consumes: existing normalized parent fields `senderName`, `text`, and `createdAt`; existing message fields and `clientMessageId` idempotency.

- [ ] **Step 1: Write failing formatter and storage tests**

Add cases named for: canonical composition; stripping a prior quote before excerpting; HTML escaping; exactly 150 Unicode characters; malformed/lookalike prefix preservation; preserving an immutable prefix while editing only reply body; same-chat parent requirement; idempotent retry before parent lookup; missing/cross-chat parent rejection; one stored row; and normal 1000-message pruning.

- [ ] **Step 2: Run the focused tests and verify RED**

Run: `php server/srv/alina/tests/ChatReplyStorageTest.php`

Expected: FAIL because `ChatReply` and `appendReply` do not exist.

- [ ] **Step 3: Implement the canonical formatter**

Create the exact `ChatReply` methods above. The only accepted prefix is the canonical anchored structure from the spec. Escape with `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`; excerpt with `mb_substr(..., 0, 150)`.

- [ ] **Step 4: Implement scoped lookup and atomic append**

Add `chat_message::findForChat(int $chatId, int $messageId): ?stdClass`. Implement `appendReply` so its transaction checks `clientMessageId` first, then resolves the scoped parent, composes/inserts one row, and prunes to 1000. Update `editMessage` to preserve a valid existing quote prefix with `replaceOwnText` while replacing only its body. Mirror behavior in both in-memory test storages.

- [ ] **Step 5: Run storage verification**

Run: `php server/srv/alina/tests/ChatReplyStorageTest.php && php server/srv/alina/tests/ChatStorageContractTest.php`

Expected: PASS with one reply row, safe canonical content, scoped lookup, retry idempotency, and pruning assertions satisfied.

### Task 2: WebSocket Reply Protocol

**Files:**
- Create: `server/srv/alina/tests/ChatReplyProtocolTest.php`
- Modify: `server/srv/alina/Services/Chat/ChatServer.php`

**Interfaces:**
- Consumes: `ChatStorage::appendReply(int, int, array): array` from Task 1.
- Consumes: `ChatReply::hasCanonicalPrefix(string): bool` from Task 1.
- Produces: version-2 `reply_message` input with `replyToMessageId`, `msg`, `clientMessageId`, and `CurrentUser`.
- Produces: one existing-format `message` broadcast containing the stored composed reply.

- [ ] **Step 1: Write failing protocol tests**

Cover admitted reply success and one-message broadcast; guest and registered repliers; replies to Robocop; missing/zero/non-numeric/cross-chat parent IDs; empty and oversized composed messages; retry without duplicate broadcast; pending/pre-join rejection; reserved-prefix rejection for ordinary creation; immutable quote preservation during editing; oversized edited combined messages; and unchanged ordinary-message behavior.

- [ ] **Step 2: Run the focused test and verify RED**

Run: `php server/srv/alina/tests/ChatReplyProtocolTest.php`

Expected: FAIL because `reply_message` is unknown.

- [ ] **Step 3: Add reply dispatch and validation**

Accept `reply_message` in version 2, after existing channel/admission checks. Reuse existing participant identity, text, client-ID, timestamp, and 16,000-character validation; call `appendReply`; broadcast only when inserted using the ordinary `message` envelope.

- [ ] **Step 4: Protect the canonical prefix**

Reject ordinary `message` text when `ChatReply::hasCanonicalPrefix()` is true. Reject an edit payload containing a canonical prefix; the server accepts only the new own-body text and storage preserves any existing quote prefix. Validate final composed reply and edited stored text against the 16,000-character limit before writing.

- [ ] **Step 5: Run all backend chat tests**

Run: `for test_file in server/srv/alina/tests/Chat*Test.php; do php "$test_file" || exit; done`

Expected: every Chat test reports PASS; expected failure-path error-log messages may appear without test failure.

### Task 3: Safe Reply Rendering, Selection, and Transport

**Files:**
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatProtocol.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatConnection.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/ChatMessage.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/index.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chat.css`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/locales/index.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-contract.mjs`

**Interfaces:**
- Consumes: `reply_message` protocol from Task 2.
- Produces: `parseReplyMessage(text: string): {quote: null | {author: string, timestamp: number, text: string}, body: string}`.
- Produces: `formatChatTimestamp(value: string | number | Date, now?: Date): string` using `HH:mm` today and `yyyy-MM-dd HH:mm` otherwise, always 24-hour.
- Produces: `chatConnection.replyMessage(replyToMessageId: string, text: string): string|false`, using the same retry-safe client-ID lifecycle as `sendMessage`.

- [ ] **Step 1: Add failing frontend contract cases**

Assert strict canonical parsing and entity decoding; malformed/lookalike markup remaining plain; dynamic timestamp formats; Reply button in the footer's left side; Edit/Delete on the right; selected-message toggle/transfer; dark-orange class; composer text unchanged on selection; editing a reply loads only its body; starting Edit clears Reply selection; Reply controls are disabled while editing; reply payload fields; selection/draft preservation after send failure; and clearing after successful send/channel change.

- [ ] **Step 2: Run the frontend contract and verify RED**

Run: `cd /home/qqq/_A001/rep/ALINA_FE && node scripts/check-chat-contract.mjs`

Expected: FAIL on missing reply parser/control/transport behavior.

- [ ] **Step 3: Implement strict parser and shared timestamp formatter**

Parse only the exact anchored canonical prefix; decode only entities emitted by the PHP formatter; pass `body` through existing `messageParts`. Move current message-header time formatting to `formatChatTimestamp` and reuse it for the quote timestamp.

- [ ] **Step 4: Implement Reply UI and local selection state**

Add a localized Reply button to each footer's left side. Add `replyingMessageId` in the chat page; toggling does not touch `draft`; only one ID is active; pass a selected prop to `ChatMessage`; clear on successful send, message deletion, channel change, and starting Edit. Disable but do not hide Reply controls while `editingMessageId` is active. When editing, initialize the composer from `parseReplyMessage(message.msg).body`, never from the canonical quote prefix.

- [ ] **Step 5: Implement reply transport**

Add `replyMessage` sending `type`, `replyToMessageId`, `msg`, `clientMessageId`, and current public user. Reuse failed-send IDs exactly as ordinary messages do. Route composer submission through this method only while a parent is selected.

- [ ] **Step 6: Implement safe rendering and exact styles**

Render quote author, formatted time, and excerpt through Vue text interpolation. Use `.reply-quote` with a `3px` purple left border, gray text, and `0.7rem` font size. Use a dark-orange background for the selected parent without changing gray active-edit, edited-blink, or persistent dark-green states.

- [ ] **Step 7: Run frontend verification without building**

Run: `cd /home/qqq/_A001/rep/ALINA_FE && node scripts/check-chat-contract.mjs && npm run check:migration && git diff --check`

Expected: all contract scripts pass and `git diff --check` is silent. Do not run `npm run build:all`.

### Task 4: Integrated Verification and Runtime Handoff

**Files:**
- Verify all files from Tasks 1–3.

**Interfaces:**
- Consumes: completed backend and frontend reply contracts.
- Produces: evidence that backend tests, frontend static contracts, syntax checks, and whitespace checks pass.

- [ ] **Step 1: Run backend syntax and behavior checks**

Run: `php -l server/srv/alina/Services/Chat/ChatReply.php && php -l server/srv/alina/Services/Chat/ChatServer.php && php -l server/srv/alina/Services/Chat/DatabaseChatStorage.php && php -l server/srv/alina/mvc/Model/chat_message.php && for test_file in server/srv/alina/tests/Chat*Test.php; do php "$test_file" || exit; done`

Expected: no syntax errors and all Chat tests PASS.

- [ ] **Step 2: Run frontend and repository checks**

Run: `cd /home/qqq/_A001/rep/ALINA_FE && node scripts/check-chat-contract.mjs && npm run check:migration && git diff --check && cd /home/qqq/_A001/rep/ALINA_BE && git diff --check`

Expected: all checks pass with no whitespace errors.

- [ ] **Step 3: Review scoped diffs and operational handoff**

Confirm no migration, no Robocop reply path, no `v-html`, no unrelated changes, and no commit. Report that the user must run `npm run build:all`; restart only the long-running local chat socket container if separately authorized at execution time.
