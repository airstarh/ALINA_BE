# Chat Rooms Directory Design

Date: 2026-10-19

## Purpose

Add an ALINA_FE page at `/#/chat/rooms` where a registered or anonymous user can discover chats for the current frontend origin. Chat data remains owned by the WebSocket chat service; the Vue page must not query the main site's database for chat information.

The initial page returns at most 1,000 rooms per list. The protocol and storage interface must already expose pagination metadata so a configurable page size and paginator can be added later without redesigning the backend contract.

## Scope

The page contains two lists:

1. Chats in which the current identity has participated.
2. Other chats for the same origin in which the current identity has not participated.

Participation and participant counts are derived exclusively from the existing `chat` and `chat_message` tables. No participant-membership table is introduced.

## Identity and participation

The frontend uses the existing ALINA current-user flow before contacting the socket:

- A registered identity is represented by `sender_type = 'user'` and its user ID.
- An anonymous identity is represented by `sender_type = 'guest'` and the persistent frontend `guestId`.

The socket does not validate users against an application database. This is consistent with the existing chat protocol.

A user has participated in a chat when at least one retained non-bot `chat_message` row matches that sender type and sender ID. Because kicking a participant deletes that participant's messages from the chat, a kicked participant ceases to count as a participant and the room moves out of that identity's "My chats" list.

Robocop and any other `sender_type = 'bot'` rows do not count as participants.

## Origin isolation

The socket derives the origin from the WebSocket HTTP handshake using the existing normalized-origin logic. Every directory query is restricted to `chat.origin` matching that normalized origin.

Rooms from `zero`, `vov`, or another frontend origin must never be mixed in one directory response, even when every site uses the same socket service and chat database.

## WebSocket protocol

Viewing the directory opens a WebSocket connection but does not join a chat, affect presence, or create a room.

The frontend sends a version 2 request conceptually shaped as:

```json
{
  "version": 2,
  "type": "list_rooms",
  "CurrentUser": {
    "id": 17,
    "guestId": null,
    "firstname": "Alice",
    "lastname": "Example",
    "nickname": "Alice",
    "emblem": "/uploads/alice.webp"
  },
  "pagination": {
    "mine": {"limit": 1000, "offset": 0},
    "others": {"limit": 1000, "offset": 0}
  }
}
```

`channel` is not required for this event. The socket validates the identity through the same public-user normalization used by chat joins. Each list has independent `limit` and `offset` integers; `offset` must be non-negative and `limit` must be positive. The service clamps each limit to a maximum of 1,000.

The response is conceptually shaped as:

```json
{
  "version": 2,
  "type": "rooms",
  "mine": {
    "rows": [
      {
        "channel": "general",
        "chatName": "General discussion",
        "participantCount": 12,
        "latestMessageAt": 1792411200000
      }
    ],
    "total": 1,
    "limit": 1000,
    "offset": 0
  },
  "others": {
    "rows": [],
    "total": 0,
    "limit": 1000,
    "offset": 0
  }
}
```

The request and response retain independent pagination metadata for both lists. The initial page uses the same values for each, but future paginator controls can change one list without resetting the other or changing the protocol.

Malformed requests return the existing version 2 error event and do not terminate the long-running worker.

## Storage queries

Each row contains:

- `channel`: `chat.channel_name_pseudo_id`.
- `chatName`: `chat.chat_name_human`.
- `participantCount`: number of distinct non-bot `(sender_type, sender_id)` pairs.
- `latestMessageAt`: maximum `chat_message.created_at`, or `null` when the chat has no messages.

"My chats" uses an existence condition for the current sender identity. "Other chats" uses the inverse condition. Both lists:

- filter by current origin;
- include aggregate participant and latest-message data;
- order by latest message descending;
- place chats without messages last;
- use deterministic chat ID ordering as the final tie-breaker;
- apply validated `limit` and `offset`;
- return the total matching chat count independently from the paged rows.

The implementation should avoid multiplying participant counts when testing current-user participation. A grouped aggregate subquery plus `EXISTS`/`NOT EXISTS`, or equivalent queries with independently verified results, is preferred.

No new table is required. Supporting indexes may be proposed only after examining the actual query plan. Any index migration must be documented separately rather than silently added.

## Frontend route and transport

Add the Vue route `/chat/rooms`, producing the hash URL `/#/chat/rooms`.

The page has its own small WebSocket directory transport rather than joining a room through the existing chat component. It:

1. obtains the current registered identity or persistent guest identity through existing frontend services;
2. connects to the existing `/ws` endpoint;
3. sends `list_rooms` with independent `limit: 1000` and `offset: 0` settings for both lists;
4. renders the `rooms` response;
5. exposes loading, empty, connection-error, reconnecting, and manual-retry states;
6. closes its socket and timers when unmounted.

The transport should accept separate limit and offset arguments for both lists even though the first UI has no paginator.

## Tables and totals

The page renders two responsive tables: "My chats" and "Other chats".

Every data row contains:

1. one-based display order number, adjusted by offset;
2. human chat name;
3. participant count;
4. latest-message date/time in ALINA's established local format `yyyy-MM-dd HH:mm`, or an empty value when no message exists.

The chat name links to `/#/chat?channel=<encoded channel>` using `target="_blank"` and `rel="noopener"`.

Every table ends with a totals row:

- order column: empty;
- name column: `TOTAL`;
- participant column: sum of participant counts in the currently displayed rows;
- latest-message column: newest message date among the currently displayed rows, or empty.

Each section heading includes the displayed room count, such as `My chats (12)`. The protocol's full matching `total` remains available for the future paginator.

The page must use the existing ALINA/Bootstrap visual system. In particular, tables use the same `table-responsive` and `table table-dark table-striped table-hover table-sm` classes already used by `AlinaTableJson`. Layout, alerts, buttons, spacing, empty rows, and totals use existing Bootstrap utility classes. Do not introduce a chat-room-specific stylesheet or new CSS rules for this page.

## Error and concurrency behavior

- Directory reads never create rooms or messages.
- Simultaneous message or policy writes may make a directory response a momentary snapshot; no cross-query transaction is required.
- A socket/storage exception becomes a client error while the worker remains alive.
- A stale response from a replaced/reconnected connection must not overwrite the current page state.
- Empty result sets are successful responses, not errors.

## Testing

Backend storage tests cover:

- registered and guest participation;
- origin isolation;
- bot exclusion;
- distinct participant counts;
- kicked-user disappearance through deleted messages;
- latest-message ordering and deterministic ties;
- empty chats sorted last;
- limits, offsets, and independent total counts.

Socket tests cover:

- `list_rooms` without joining a channel;
- identity and pagination validation;
- response shape;
- storage failures becoming non-fatal error events;
- no room creation or presence side effects.

Frontend tests cover:

- the `/chat/rooms` route;
- request shape and reconnect cleanup;
- two tables and empty/error/loading states;
- safe new-tab room links;
- row numbering;
- displayed totals.
- use of the established ALINA/Bootstrap table classes without a custom stylesheet;
- the exact `yyyy-MM-dd HH:mm` date format;
- hash-only generated chunk names that cannot collide with protected Nginx path prefixes.

The normal JavaScript SPA build remains subject to explicit user approval immediately before execution.

## Build and local activation

ALINA_BE is edited directly in the locally served repository tree, so it has no separate local deployment or copy step. Ordinary PHP HTTP changes become active on the next request. Changes to the long-running WebSocket server require restarting only the local `php82chat` container.

ALINA_FE requires `npm run build:all` to publish both the `vov` and `zero` builds into their mounted web directories.

Generated non-entry JavaScript chunks use hash-only filenames (`[hash].js`). The existing Nginx protected-path expression contains `/run` without a path boundary, so a dependency-derived filename such as `runtime-core.esm-bundler.<hash>.js` is incorrectly matched and served as HTTP 403. Hash-only chunk names avoid that collision without changing Nginx.

No migration is currently planned unless query-plan review demonstrates a required supporting index.
