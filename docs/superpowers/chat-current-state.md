# Text chat: current implemented state

Last reviewed: 2026-10-20

This is the current operational reference for the Vue 3 text chat. The dated
designs and plans under `docs/superpowers/specs/` and `docs/superpowers/plans/`
record how individual features were designed; this document describes the
behavior that is implemented now.

## Entry points and ownership

- Vue room: `/#/chat?channel=<channel>`.
- Vue room directory: `/#/chat/rooms`.
- `src/views/Chat/index.vue` is the routed page; reusable chat behavior lives in
  `ALINA_FE/src/components/Chat/`.
- The browser uploads files through the normal ALINA HTTP application. File
  bytes never pass through WebSocket.
- The PHP CLI WebSocket service owns room state, presence, chat protocol and
  database access through the database selected by the CLI ALINA bootstrap.
- A channel is a string of 1–128 ASCII letters, numbers, underscores or hyphens.
- A room is uniquely identified by normalized browser origin plus channel. One
  socket and database can therefore serve several sites without mixing rooms.

## Identity and private rooms

Registered participants use `user:<numeric-id>`. Guests receive a persistent
UUID stored by the frontend and use `guest:<uuid>`. The UUID is stable across
tabs and reloads in the same browser profile; two independent browser profiles
receive different UUIDs.

The guest's base label is `Guest <first-eight-UUID-characters>`. A guest may set
a 1–25 character nickname, displayed as `Nickname (Guest abcdef12)`. The UUID,
not the nickname or socket connection ID, remains the identity. Guest renames
are synchronized across live tabs and update retained message snapshots in the
current room. Administrators may rename a guest from a message row.

Participant avatars and PRIVATE controls open a separate room in a new tab. The
private channel is derived deterministically from the two stable identities:
registered IDs sort before a guest identity; like identity types sort
canonically. Both participants therefore compute the same channel.

The socket accepts the public identity supplied by the client and does not look
up or authenticate users against each consumer site's user database.

## Persistence, history and reconnects

Rooms and messages are stored in the `chat` and `chat_message` tables. Each room
retains its newest 1,000 rows. Joining returns the newest 50 rows. History
survives reloads, temporary disconnects, the last participant leaving, and
WebSocket-worker restarts.

The 120-second empty-room timer now releases only live in-memory room objects;
it does not delete database history. Rejoining during that interval cancels the
cleanup.

Vue creates a `clientMessageId` before sending. Database uniqueness makes sends
and reconnect retries idempotent. Mention operations use the composite unique
key `(chat_id, client_message_id, sender_type, sender_id)` so one client action
can safely store several target service rows plus the ordinary message.

For mentions, `client_message_id` is also a deliberate relationship key. Each
`user_service` row represents one mentioned target: its `sender_id`, retained
name and emblem belong to that target. The ordinary non-`user_service` row with
the same `(chat_id, client_message_id)` belongs to the participant who initiated
the mention. Future behavior can therefore recover the initiator by loading the
shared group and selecting its ordinary row. Current UI behavior does not yet
consume this relationship, but it must be preserved by later changes.

This relationship exists only while the corresponding rows remain retained.
Deleting the initiator row or allowing retention to prune it can leave a target
service row without its initiator row. Any future feature that requires a
permanent initiator relationship must define deletion and retention behavior or
move the relationship into explicit durable metadata.

The client reconnects after unexpected closure with capped exponential delay,
preserves unsent per-channel drafts, and logs WebSocket close code, reason and
`wasClean`. A manual reconnect control is also available. Ratchet sends protocol
ping frames every 20 seconds; browsers answer automatically, keeping idle proxy
tunnels active. A deliberate kick suppresses automatic reconnect.

## Messages and composer

- Send with the button or Ctrl+Enter; Enter inserts a new line.
- Message bodies are rendered as escaped text. Complete fenced code blocks use
  `pre`/`code` with a black background, green monospace text and horizontal
  scrolling without wrapping.
- HTTP(S) URLs are linkified. Images, videos and audio use native preview tags;
  other URLs remain downloadable links with a copy control.
- Registered users can select several files. `/FileUpload` stores them through
  the web application, then sender-origin absolute URLs are inserted into the
  draft. Guests cannot upload because the existing HTTP upload route requires
  authentication.
- Registered users can record up to five minutes of audio. The client selects a
  supported Opus WebM, Opus Ogg or MP4 audio format, uploads it through the same
  HTTP path, and sends a `VOICE:<absolute-url>` marker. Voice messages use native
  audio controls. Leaving or changing rooms cancels an unfinished recording.
- A long-lived tab keeps at most 500 displayed message objects in the DOM; this
  does not change database retention.
- When the reader is away from the bottom, new messages increment a counter and
  a control jumps to the latest message.

Messages are limited to 16,000 bytes. The top-level WebSocket JSON frame limit
is 65,536 bytes.

## Editing, deletion and replies

The message owner and frontend-recognized admin/moderator/privileged roles see
the appropriate edit/delete controls. Editing reuses the main composer and
preserves the original message identity, author and timestamp. Incoming edits
replace the complete normalized message on every client; incoming deletions
remove it.

Reply selects one message, keeps the existing composer text, and stores a safe
canonical quote prefix with the original author, timestamp and a maximum
150-character excerpt. Replies never nest quote chains. Quote fields render as
plain escaped text; links and markup in a quote are not activated. Editing a
reply changes only its body.

The socket validates room scoping and payload shape for mutations, but it does
not validate application roles or message ownership. Role and ownership powers
are currently a frontend trust decision, not a security boundary.

## Mentions

A message may mention up to 22 distinct registered users or guests retained in
the same origin. Typing `@` at the start of the composer or after a space opens
up to five recent candidates. Vue searches its cached directory locally; after
three characters and no local match, it performs a 250 ms debounced WebSocket
search. The socket replaces client-supplied names with canonical retained
snapshots, rejects self/unknown/cross-origin targets, and stores the complete
operation atomically.

Each target receives a `user_service` notification row followed by the sender's
ordinary message. That row makes the room a **My chats** room for the target and
allows the target to enter a confirmation-required room without separate
approval.

New service rows store a language-neutral `MENTION:` payload. Vue renders it as:

- English: **Target!!!** Caller calls you!
- Russian: **Target!!!** Caller взывает!

The target is bold and light white. Historical plain-text notifications such as
`Caller mentions Target` remain unchanged and render exactly as stored. A
mention notification has no avatar, Reply or Edit control. The target or a
frontend-recognized admin/moderator/privileged user can delete the individual
service row.

## Rooms, admission and management

`/#/chat/rooms` opens a directory-only socket and does not join or create a
room. It shows origin-scoped **My chats** and **Other chats** tables with human
room name, distinct participant count and latest activity. Each section has
independent `limit`/`offset` protocol metadata; the current page requests up to
1,000 rows per section. Mention service rows count as target participation;
Robocop messages do not.

Room controls can:

- rename a chat, up to 200 characters;
- allow or deny anonymous participants;
- enable or disable confirmation for new participants;
- approve pending participants;
- kick a connected participant.

The first participant in an empty new room enters immediately. A participant
with retained participation evidence also enters immediately. Other arrivals
wait when confirmation is enabled. Kicking closes all live connections for the
identity, deletes that participant's ordinary and associated service rows from
the room, and appends a Robocop removal message.

These management controls are exposed according to frontend role helpers. The
socket currently performs no independent application-role authorization.

## Presence and layout

The participant sidebar includes retained participants even when they are
offline. Presence separates three concepts:

- `online`: connected to any admitted chat for the same origin;
- `inChat`: connected to this room;
- `typing`: actively typing in this room, expiring after six seconds.

The UI displays **Typing…**, **In chat**, or **Not in chat**; a green/gray dot
shows origin-wide online state separately. Multiple connections for the same
stable identity collapse into one participant entry. Join/leave activity never
adds messages to history.

The participant sidebar collapses on small screens. Chat hides the main menu and
footer by default through the existing `PageSettings` mechanism; a header button
temporarily toggles them and leaving chat restores the prior setting. The header
also exposes connection state, manual reconnect, chat-link copy and video-chat
launch controls.

## Protocol and compatibility

Version 2 client commands currently include:

`join`, `message`, `reply_message`, `edit_message`, `delete_message`, `typing`,
`approve`, `rename`, `rename_guest`, `set_anonymous`, `set_confirmation`,
`kick`, `list_rooms`, `mentions_directory`, and `mentions_search`.

Important server events include:

`history`, `joined`, `join_pending`, `join_approved`, `message`,
`message_edited`, `message_deleted`, `presence`, `chat_state`, `join_request`,
`participant_kicked`, `guest_renamed`, `rooms`, `mentions_directory`,
`mentions_search`, and `error`.

The legacy PHP chat remains supported by the compatibility branch in
`ChatServer`; it does not receive all version 2 presence and management events.

## Database and activation

The required schema history is:

- `admin/sql/history/0001.chat.base-tablkes.sql` — base chat tables;
- `admin/sql/history/0002.chat.emblem.sql` — retained sender emblem;
- `admin/sql/history/003chat.unique-key.sql` — grouped-message unique key.

Schema files are historical/manual migrations; reviewing documentation does not
execute them. Backend protocol changes require restarting the long-running chat
worker. Frontend source changes require the appropriate explicitly authorized
SPA build before deployment.

## Primary implementation and verification files

Backend:

- `server/srv/alina/Services/Chat/ChatServer.php`
- `server/srv/alina/Services/Chat/ChatServerRunner.php`
- `server/srv/alina/Services/Chat/ChatStorage.php`
- `server/srv/alina/Services/Chat/DatabaseChatStorage.php`
- `server/srv/alina/Services/Chat/ChatMentions.php`
- `server/srv/alina/Services/Chat/ChatReply.php`
- `server/srv/alina/mvc/Model/chat.php`
- `server/srv/alina/mvc/Model/chat_message.php`
- `server/srv/alina/tests/Chat*.php`

Frontend:

- `src/views/Chat/index.vue`
- `src/views/Chat/rooms.vue`
- `src/components/Chat/`
- `scripts/check-chat-contract.mjs`
- `scripts/check-chat-rooms-contract.mjs`
- `scripts/check-chat-voice-recorder-contract.mjs`
- `scripts/check-chat-voice-ui-contract.mjs`
- `scripts/check-chat-mentions-contract.mjs`

Safe verification does not require a build:

```bash
for test_file in server/srv/alina/tests/Chat*.php; do
    php "$test_file" || exit
done
```

```bash
cd /home/qqq/_A001/rep/ALINA_FE
npm run check:migration
```

