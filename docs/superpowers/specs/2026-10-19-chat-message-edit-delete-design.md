# Chat Message Edit and Delete Design

Date: 2026-10-19

## Purpose

Allow a chat participant to edit or delete an existing persisted message from the Vue chat UI. The actions are visible only to the message owner or to a frontend user with the `admin` or `moderator` role. Authorization remains a Vue UI concern: the WebSocket server does not validate application users or roles.

This design extends the existing version 2 chat protocol and retains the current monolithic storage model. It requires no database migration.

## User interface

Each message has a footer directly below its text. The existing message time remains right-aligned, and the edit/delete actions appear on the right beneath it.

The controls use the existing one-character translation symbols from `src/locales/index.js`:

- edit: `$t('i_edit')`;
- delete: `$t('i_delete')`.

Do not introduce a new icon library or hard-code alternate image assets.

The controls are rendered only when either condition is true:

- the message `participantId` equals the current socket `selfId`;
- `CurrentUser.isAdmin()` or `CurrentUser.isModerator()` returns true.

This visibility rule is intentionally not security enforcement. The socket continues the established ALINA chat philosophy of serving chat operations without checking application roles or user validity.

## Editing workflow

Clicking the edit action:

1. copies the message text into the existing main composer textarea;
2. records the edited message ID in page state;
3. gives the original message a gray background so the editing target remains visible;
4. focuses the composer.

Submitting the composer while an edit is active sends an edit event instead of creating a new message. The composer must expose a way to cancel editing and return to ordinary message composition. Cancelling does not alter the stored message.

A successful edit replaces the displayed text in every connected client while preserving the original message identity, sender, and creation timestamp. No separate edited timestamp or edited marker is required in this iteration.

If the targeted message is deleted while the local client is editing it, edit mode ends. The text already present in the composer is retained as a normal draft rather than being discarded.

## Deletion workflow

Clicking delete first calls the browser-native `window.confirm()` dialog. Cancel performs no operation. Confirmation sends a delete event.

A successful delete permanently removes the row from `chat_message` and broadcasts the deletion to connected clients. Every client removes that message from its displayed list. If the deleted message is the current local edit target, the client follows the edit-cancellation behavior described above.

## WebSocket protocol

Both operations require an admitted version 2 connection that has joined the message's chat.

### Edit request

```json
{
  "version": 2,
  "type": "edit_message",
  "channel": "general",
  "messageId": "123",
  "text": "Corrected text"
}
```

The socket validates:

- `messageId` is a positive decimal identifier;
- `text` is a string, remains non-empty after trimming, and is at most 16,000 bytes;
- the identified message belongs to the currently joined chat.

The socket updates only `message_text`. It then broadcasts:

```json
{
  "version": 2,
  "type": "message_edited",
  "channel": "general",
  "message": {
    "id": "123",
    "channel": "general",
    "msg": "Corrected text",
    "CurrentUser": {},
    "participantId": "user:17",
    "timestamp": 1792411200000,
    "clientMessageId": "original-client-id"
  }
}
```

The event contains the complete normalized message object so clients replace the row atomically while retaining its original sender, timestamp, and client message ID.

### Delete request

```json
{
  "version": 2,
  "type": "delete_message",
  "channel": "general",
  "messageId": "123"
}
```

The socket validates the identifier and verifies that the message belongs to the currently joined chat. It permanently deletes the row and broadcasts:

```json
{
  "version": 2,
  "type": "message_deleted",
  "channel": "general",
  "messageId": "123"
}
```

Missing messages or messages belonging to another chat return the existing version 2 error event. Storage failures remain non-fatal to the long-running worker.

## Storage contract

Extend `ChatStorage` with these exact operations:

```php
public function editMessage(int $chatId, int $messageId, string $text): array;
public function deleteMessage(int $chatId, int $messageId): bool;
```

Both methods scope mutations by `chat_id` and message ID. `editMessage` returns the normalized updated row. Neither method accepts or checks an acting user identity or role.

## Frontend transport and state

`createChatConnection` exposes `editMessage(messageId, text)` and `deleteMessage(messageId)`. They use the existing version 2 send path and report send failure without clearing the composer or local message.

The chat page owns the edit target and composer behavior. Incoming events are authoritative:

- `message_edited` replaces the matching displayed message;
- `message_deleted` removes the matching displayed message;
- deletion of the active edit target exits edit mode while retaining composer text.

`ChatMessage.vue` remains responsible for message presentation and emits edit/delete intentions to the parent. It receives whether controls are allowed and whether the row is the active edit target.

## Styling

Reuse existing ALINA/Bootstrap classes and the established chat stylesheet where necessary. The active edit target has a gray background. Controls remain compact, keyboard-accessible buttons with translated labels/titles. Do not introduce another icon package.

## Testing

Backend storage and protocol tests cover:

- editing only a message in the joined chat;
- deletion only from the joined chat;
- invalid, missing, and cross-chat message IDs;
- empty and oversized edit text;
- persisted history after editing and deletion;
- broadcast payloads;
- storage failures becoming non-fatal error events.

Frontend contract tests cover:

- owner/admin/moderator action visibility;
- ordinary participants not seeing controls for another sender;
- existing locale icon keys being used;
- edit text loading into the main composer;
- gray active-target state;
- submit choosing edit instead of new-message creation;
- cancel restoring normal composer behavior;
- native confirmation before deletion;
- incoming edit and delete synchronization;
- deletion during editing preserving the composer draft.

## Activation

No migration is required. ALINA_FE changes require `npm run build:all`. Because the WebSocket server is long-running, backend protocol/storage changes become active after restarting the local `php82chat` container. No separate local ALINA_BE deployment step is needed.
