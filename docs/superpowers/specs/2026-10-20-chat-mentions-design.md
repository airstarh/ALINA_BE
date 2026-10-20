# Chat Mentions Design

## Goal

Allow one ALINA creature to mention up to 22 other creatures in a chat message. A mention must make the room appear under the target's **My chats**, admit the target without confirmation, remain visible as a special service notification, and reuse the existing `chat_message` table.

## Vocabulary

- `user`: an authenticated creature using ALINA, regardless of whether it is human, robot, AI, or another kind of registered creature.
- `guest`: an unregistered creature represented by its stable guest UUID.
- `user_service`: a system-generated message associated with one mentioned creature.
- `bot`: an ordinary system bot identity such as Robocop. Bot messages do not establish participation.

## Storage Model

Every send operation has one Vue-generated `client_message_id`. All mention service rows and the initiator's ordinary row share that ID.

For each distinct mention target, the socket stores one `chat_message` row:

```text
sender_type       = user_service
sender_id         = mentioned creature ID or guest UUID
sender_name       = mentioned creature's latest known name
sender_emblem     = mentioned creature's latest known emblem
message_text      = MENTION:<URL-encoded JSON containing target and caller>
client_message_id = the initiator message's client_message_id
```

The socket then stores the initiator's ordinary message unchanged, using the same `client_message_id` and the initiator's ordinary `user` or `guest` sender type.

The shared ID defines one atomic client operation and intentionally preserves
the relationship between every target notification and its initiator. A
`user_service` row describes the mentioned target; the non-`user_service` row
with the same `(chat_id, client_message_id)` identifies the participant who
initiated the mention. This relationship is not consumed by the current UI, but
it is part of the storage contract for future mention features and must not be
discarded as incidental retry metadata. Initiator identity grants no
mention-management power in this version.

The relationship is retention-bound with the current schema. If the ordinary
initiator row is deleted or pruned while a service row remains, that remaining
row cannot recover its initiator from the group. A future feature requiring a
permanent relationship must define group-aware deletion/retention semantics or
introduce explicit durable metadata.

The unique database key is:

```sql
UNIQUE (chat_id, client_message_id, sender_type, sender_id)
```

No new table or column is introduced.

## Atomicity and Retry Safety

The socket processes a mention send in one database transaction:

1. Lock the current chat row.
2. Search the chat for the `client_message_id` group.
3. If the complete initiator row already exists, return the existing operation without inserting anything.
4. Validate and insert one `user_service` row per target, in the order supplied after deduplication.
5. Insert the initiator's ordinary message last.
6. Prune the room to its latest 1,000 rows once after the complete group is inserted.
7. Commit.
8. Broadcast the inserted service messages in order, followed by the ordinary message.

The chat-row lock serializes competing sends for that room while checking and inserting a group. Any insertion failure rolls back the entire group. There are never partial mention groups.

## Mention Target Validation

- A message may contain at most 22 distinct targets.
- Duplicate targets are collapsed before insertion.
- Self-mentions are rejected.
- Each target must have retained `user`, `guest`, or `user_service` history in the same origin.
- A target from another origin is rejected.
- The socket does not trust names or emblems sent by Vue. It loads the latest retained snapshot for the target in the current origin.
- Registered numeric IDs and guest UUIDs are structurally distinct and may share the same `user_service` sender type safely.

## Mentionable Creature Directory

Vue requests an origin-wide mention directory through WebSocket. It does not query the consumer application's database directly.

The socket returns at most 1,000 distinct creatures, ordered by their latest activity in the origin descending. Candidates come from retained messages whose type is:

```sql
IN ('user', 'user_service', 'guest')
```

Rows are collapsed by `sender_id`. Display data prefers the latest genuine `user` or `guest` snapshot and falls back to the latest `user_service` snapshot.

The current creature is excluded.

## Selector and Search UX

- `@` activates mention search only at the beginning of the textarea or immediately after a space.
- Vue searches its cached 1,000 candidates case-insensitively using ordinary contiguous substring matching, equivalent to `LIKE '%text%'`.
- At most five candidates are visible.
- Already selected creatures and the current creature are excluded.
- If local search has no matches and at least three characters follow `@`, Vue sends a debounced remote search request through WebSocket.
- The debounce interval is 250 milliseconds.
- Remote search is scoped to the current origin, escapes SQL wildcard characters, uses parameterized SQL, orders by latest activity descending, and returns at most five distinct creatures.
- Remote results are merged into the Vue cache without removing selected targets.

Selecting a candidate inserts its exact visible mention text into the textarea and adds its structured identity to `listTaggedUsers`. The inserted form is:

```text
@<sender_name>
```

If the exact inserted mention text is removed or modified so it no longer equals the selected `sender_name`, Vue removes that creature from `listTaggedUsers`. Vue performs a final synchronization and deduplication immediately before sending.

## Socket Protocol

The ordinary message command gains an optional `mentions` array. Every item contains the stable target identity needed for lookup; supplied display fields are hints only and are replaced by the server's canonical retained snapshot.

The socket adds commands for:

- loading the initial origin-wide mention directory, with a hard limit of 1,000;
- remotely searching mention candidates, with a minimum query length of three and a hard result limit of five.

Malformed targets, more than 22 targets, self-mentions, unknown origin identities, and invalid search requests produce protocol errors without persistence.

## Participation and Admission

For a registered creature, room participation evidence is:

```text
sender_id = current user ID
AND sender_type IN ('user', 'user_service')
```

For a guest:

```text
sender_id = current guest UUID
AND sender_type IN ('guest', 'user_service')
```

A retained `user_service` row therefore:

- places the room in the target's **My chats**;
- removes it from the target's **Other chats**;
- admits the target automatically even when `is_confirmation_required = 1`;
- stops providing these effects when the row is deleted or ages out of the retained 1,000 messages, unless another qualifying row remains.

Participant totals count a creature only once when both ordinary and `user_service` rows exist. Bot rows remain excluded.

## Service Message UI

New `user_service` mention messages contain a language-neutral structured
payload. Vue renders the target first, in bold light-white text, followed by the
caller and a localized action:

- English: `Target!!! Caller calls you!`
- Russian: `Target!!! Caller взывает!`

Plain-text mention notifications created before this payload was introduced are
not rewritten or reinterpreted; they remain visible exactly as stored.

`user_service` messages use a dedicated compact notification presentation:

- no avatar;
- no author-name heading;
- informational `message_text` only;
- timestamp formatted as `HH:mm` today and `yyyy-MM-dd HH:mm` for older messages;
- no Reply control;
- no Edit control;
- Delete control only when permitted.

Delete permission follows the existing frontend-defined power model. A `user_service` row may be deleted by:

- the mentioned creature whose ID equals `sender_id`;
- an origin administrator;
- an origin moderator.

The initiator receives no deletion power from the shared `client_message_id` in this version. Deleting a service row deletes only that mention row, not the initiator's ordinary message or other targets in the group.

## Retention

Mention rows are ordinary retained chat rows and count toward the room's 1,000-message limit. Pruning happens after inserting the complete operation so the group is never partially removed during insertion. Later retention may naturally age individual rows out according to existing oldest-first behavior.

## Compatibility

- Messages without a `mentions` array retain their current behavior.
- Existing rows remain valid under the expanded composite unique key.
- Existing `user`, `guest`, and `bot` rendering remains unchanged.
- No consumer application database lookup is introduced.
- No JavaScript SPA build, migration execution, deployment, or container restart is part of implementation verification without explicit authorization.

## Primary Files

Backend:

- `server/srv/alina/Services/Chat/ChatServer.php`
- `server/srv/alina/Services/Chat/ChatStorage.php`
- `server/srv/alina/Services/Chat/DatabaseChatStorage.php`
- `server/srv/alina/mvc/Model/chat.php`
- `server/srv/alina/mvc/Model/chat_message.php`
- backend chat protocol and storage tests

Frontend:

- `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/index.vue`
- `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/ChatMessage.vue`
- `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatConnection.js`
- `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatProtocol.js`
- `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chat.css`
- `/home/qqq/_A001/rep/ALINA_FE/src/locales/index.js`
- frontend chat contract checks
