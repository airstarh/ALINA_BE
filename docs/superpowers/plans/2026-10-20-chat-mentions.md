# Chat Mentions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add origin-wide, searchable chat mentions that persist one `user_service` notification per target and make mentioned creatures participants without adding a table or column.

**Architecture:** Vue maintains a 1,000-creature mention cache, sends stable mention identities with one ordinary message operation, and renders `user_service` rows specially. The socket validates targets against retained origin history and asks storage to insert all service rows plus the initiator row as one chat-locked transaction sharing one `client_message_id`. Existing room/admission queries normalize `user_service` evidence with the target's ordinary sender type.

**Tech Stack:** PHP 8, Ratchet WebSocket server, Illuminate database/query builder, MySQL, Vue 3, native JavaScript contract scripts.

**Spec:** `docs/superpowers/specs/2026-10-20-chat-mentions-design.md`

## Global Constraints

- Maximum 22 distinct mentions per message.
- Initial origin directory limit: 1,000; visible selector and remote-search limit: 5.
- Remote search starts only after three characters and uses a 250 ms frontend debounce.
- All rows in one send operation share the Vue-generated `client_message_id`.
- No new database table or column; use `sender_type = user_service`.
- Service messages store the target's ID, name, and emblem but suppress those fields in their UI presentation.
- Mention insertion is atomic, retry-safe, self-mention-safe, and current-origin-only.
- Mention initiators receive no additional management power.
- Do not create a Git commit.
- Do not execute migrations, builds, Docker operations, deployments, or container restarts without explicit authorization.
- Preserve unrelated working-tree changes.

## Review Focus

- Concurrent retries using the same `client_message_id` must leave exactly one complete group, never duplicate or partial service rows.
- Numeric user IDs and guest UUIDs must both normalize correctly through `user_service` for admission, room membership, ownership, and participant totals.
- A stale or malicious Vue target with a forged name, foreign-origin ID, duplicate ID, or self ID must not create a service row.
- Removing or altering inserted mention text before send must remove the structured target, preventing hidden mentions.
- Deleting one service row must affect only that target, leaving the ordinary message and sibling mention rows intact.

---

## Task 1: Group-aware storage and mention candidate queries

**Files:**

- Modify: `server/srv/alina/mvc/Model/chat_message.php`
- Modify: `server/srv/alina/mvc/Model/chat.php`
- Modify: `server/srv/alina/Services/Chat/ChatStorage.php`
- Modify: `server/srv/alina/Services/Chat/DatabaseChatStorage.php`
- Modify: `server/srv/alina/tests/support/ChatTestSupport.php`
- Create: `server/srv/alina/tests/ChatMentionStorageTest.php`

**Interfaces:**

- Produces: `ChatStorage::mentionCandidates(string $origin, string $senderType, string $senderId, string $query, int $limit): array` returning canonical `{senderType, senderId, senderName, senderEmblem, latestActivityAt}` entries.
- Produces: `ChatStorage::appendMessageGroup(int $chatId, array $message, array $targets): array` returning `{messages: array, inserted: bool}` in service-first, initiator-last order.
- Produces: `chat_message::findGroup(int $chatId, string $clientMessageId): array` and group-aware unique-key metadata.
- Preserves: `appendMessage()` and `appendReply()` behavior for non-mention operations.

- [ ] Write failing storage tests proving one shared ID can store two `user_service` rows plus one initiator row, retry returns the existing group, target order is stable, and a failed insert rolls the whole group back.
- [ ] Add failing tests that candidate lookup is origin-scoped, deduplicates `user`/`guest`/`user_service` by `sender_id`, prefers the latest genuine snapshot, falls back to the latest service snapshot, excludes the current creature, applies case-insensitive substring search, escapes `%`/`_`, orders by latest activity, and respects limits 1,000 and 5.
- [ ] Add failing tests that `hasParticipantMessage()`, room `mine`/`others`, automatic admission evidence, and participant totals normalize `user_service` with numeric users and UUID guests without double counting; bots remain excluded.
- [ ] Run `php server/srv/alina/tests/ChatMentionStorageTest.php` and verify failures are caused by missing group/candidate behavior.
- [ ] Update model unique-key metadata to `['chat_id', 'client_message_id', 'sender_type', 'sender_id']`; implement group lookup, candidate queries, normalized participation, and normalized distinct participant aggregation.
- [ ] Implement `appendMessageGroup()` with a chat-row `FOR UPDATE` lock, initiator-row retry detection, one transaction, one prune after insertion, and post-rollback retry lookup. Update the in-memory test storage with the same public contract.
- [ ] Run the new storage test plus all existing chat storage, room-directory, reply, mutation, history, and origin tests; verify all pass.

## Task 2: WebSocket mention protocol and atomic broadcasting

**Files:**

- Modify: `server/srv/alina/Services/Chat/ChatServer.php`
- Create: `server/srv/alina/Services/Chat/ChatMentions.php`
- Modify: `server/srv/alina/tests/support/ChatTestSupport.php`
- Create: `server/srv/alina/tests/ChatMentionProtocolTest.php`

**Interfaces:**

- Consumes: `mentionCandidates()` and `appendMessageGroup()` from Task 1.
- Produces: message payload field `mentions: Array<{senderType: 'user'|'guest', senderId: string}>`.
- Produces: commands `mentions_directory` and `mentions_search`, responses carrying canonical candidate arrays.
- Produces: protocol messages with explicit `senderType` and `senderId` in addition to existing `participantId`.

- [ ] Write failing protocol tests for directory limit 1,000, search minimum length 3/result limit 5, and current-origin candidate responses.
- [ ] Write failing send tests for zero mentions (unchanged behavior), one and 22 mentions, duplicate collapse, 23-target rejection, self rejection, foreign/unknown target rejection, and server-side replacement of forged names/emblems.
- [ ] Add failing tests that service rows broadcast before the ordinary row, all rows share the client ID, a retry broadcasts no duplicate rows, and service text is `<initiator> mentions <target>`.
- [ ] Run `php server/srv/alina/tests/ChatMentionProtocolTest.php` and verify expected missing-protocol failures.
- [ ] Implement focused constants and pure validation/normalization in `ChatMentions` (`MAX_PER_MESSAGE = 22`, directory 1,000, result 5, search minimum 3), then wire the three protocol flows into `ChatServer`.
- [ ] Extend `protocolMessage()` with `senderType` and `senderId`, call `appendMessageGroup()`, and broadcast only newly inserted rows in returned order.
- [ ] Run the new protocol test and the complete backend chat test set; verify all pass.

## Task 3: Frontend mention matching and synchronization module

**Files:**

- Create: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatMentions.js`
- Create: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-mentions-contract.mjs`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/package.json`

**Interfaces:**

- Consumes: canonical candidate objects from Task 2.
- Produces: `activeMentionQuery(text, caret): {start: number, query: string}|null`, active only at index 0 or after a literal space.
- Produces: `filterMentionCandidates(candidates, query, selected, self, limit = 5): array` using case-insensitive contiguous substring matching and recency order.
- Produces: `synchronizeSelectedMentions(text, selected): array` retaining only exact `@${senderName}` occurrences and deduplicating by target identity.
- Produces: `insertMention(text, caretRange, candidate): {text, caret, mention}`.

- [ ] Write failing contract cases for start/after-space activation, no email/mid-token activation, case-insensitive `%de%` behavior, five-result cap, self/selected exclusion, exact-text removal, duplicate collapse, and names containing spaces or regex punctuation.
- [ ] Run `node scripts/check-chat-mentions-contract.mjs` and verify it fails because the module is absent.
- [ ] Implement the four pure functions without DOM dependencies; preserve candidate recency order rather than introducing fuzzy ranking.
- [ ] Add `check:chat-mentions` to `package.json` and the non-build aggregate check.
- [ ] Run the mention contract and existing chat frontend contract; verify both pass.

## Task 4: Frontend socket transport and retry-safe mention payloads

**Files:**

- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatConnection.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-contract.mjs`

**Interfaces:**

- Consumes: Task 2 command/event names and candidate shape.
- Produces: `sendMessage(text, mentions = [], requestedMessageId = '')` while preserving the same generated ID and mentions on a failed-send retry.
- Produces: `loadMentionDirectory()` and `searchMentions(query)` transport methods.
- Emits: canonical mention directory/search events through the existing `onEvent` callback.

- [ ] Add failing transport assertions for initial directory request, remote search request, structured mention send, retry reusing the original ID and target list, and ordinary send backward compatibility.
- [ ] Run `node scripts/check-chat-contract.mjs` and verify the new assertions fail for missing transport behavior.
- [ ] Implement the transport methods and make the retry cache key include normalized mention identities so two drafts with equal text but different targets cannot share retry state.
- [ ] Run the chat and mention contracts; verify they pass.

## Task 5: Mention selector and chat composer integration

**Files:**

- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/index.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chat.css`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/locales/index.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-mentions-contract.mjs`

**Interfaces:**

- Consumes: pure functions from Task 3 and transport methods/events from Task 4.
- Produces: in-memory `mentionCandidates` (maximum 1,000), `listTaggedUsers` (maximum 22), five-row selector, and 250 ms zero-local-result remote fallback.

- [ ] Add failing component contract assertions that the selector is positioned above the textarea, contains at most five options, supports keyboard/click selection, and uses the existing chat visual variables rather than introducing a separate design system.
- [ ] Add failing behavior assertions for initial directory loading after admission, local filtering, three-character/250 ms remote fallback only when local results are empty, cache merging, selection insertion, text-edit synchronization, 22-target enforcement, and state reset on channel change.
- [ ] Run the frontend mention contract and verify the UI assertions fail for missing integration.
- [ ] Integrate the cache and selector into `index.vue`; synchronize mentions on every draft change and immediately before send; pass the structured list to `sendMessage()` without otherwise changing the message text.
- [ ] Add localized selector/status/error copy and minimal scoped chat CSS for a five-row panel above the composer.
- [ ] Run the mention, chat, Vue 3, and Bootstrap contract checks; verify all pass.

## Task 6: Dedicated `user_service` rendering and deletion ownership

**Files:**

- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/ChatMessage.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/index.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chat.css`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-contract.mjs`

**Interfaces:**

- Consumes: explicit message `senderType`/`senderId` fields from Task 2.
- Produces: compact service notification rendering and `canDeleteServiceMessage(message, currentUser)` semantics based on target owner/admin/moderator only.

- [ ] Add failing component assertions that `user_service` suppresses avatar, author heading, Reply, and Edit; retains agreed timestamp and informational body; and conditionally shows Delete.
- [ ] Add failing ownership assertions for registered target, guest target, non-target initiator, admin, and moderator.
- [ ] Run `node scripts/check-chat-contract.mjs` and verify failures identify missing service rendering/ownership.
- [ ] Implement the dedicated branch and ownership logic. Delete continues to send the existing row-ID `delete_message` command, so sibling service rows and the initiator message remain untouched.
- [ ] Run all frontend chat contracts and verify they pass.

## Task 7: Documentation and complete non-operational verification

**Files:**

- Modify: `_GITOUT/ai/002.task.chat/001.chat.plan.md`
- Verify: all files changed by Tasks 1–6

- [ ] Update the master chat notes with mention vocabulary, limits, grouped ID semantics, admission/membership effects, selector behavior, and service-message powers.
- [ ] Run every backend `server/srv/alina/tests/Chat*.php` test and record zero failures.
- [ ] Run `npm run check:migration` from `/home/qqq/_A001/rep/ALINA_FE` and record zero failures.
- [ ] Run PHP syntax checks on every changed PHP file and Vue/compiler contract checks on changed SFCs.
- [ ] Run `git diff --check` in ALINA_BE and ALINA_FE.
- [ ] Review the final diff for unrelated edits, schema additions beyond the approved index, accidental role expansion for initiators, generated build assets, and operational changes.
- [ ] Do not run the migration, SPA build, Docker, deployment, or restart commands; report the exact manual next steps instead.

## Manual Acceptance Checklist

- [ ] Typing `@` at textarea start or after a space opens no more than five recent matches; email-like text does not.
- [ ] Local `de` finds names containing contiguous `de`, case-insensitively; remote lookup starts only at three characters and zero local matches.
- [ ] Selecting several targets inserts visible names; deleting or altering one removes its hidden structured mention.
- [ ] Sending creates one special notification per distinct target followed by one unchanged ordinary message; all rows share one client ID.
- [ ] A mentioned offline user or guest sees the room in **My chats** and enters a confirmation-required room without approval.
- [ ] Service notifications show only text, agreed time, and authorized Delete; no avatar, name heading, Reply, or Edit.
- [ ] Target owner/admin/moderator can delete one mention; initiator cannot unless separately privileged; ordinary and sibling rows remain.
- [ ] Retrying the same send creates no duplicate or partial group.
