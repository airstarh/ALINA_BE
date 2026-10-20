# Chat Message Replies Design

## Goal

Allow a participant to select any visible chat message as the parent of their next message. The stored reply contains one compact, non-nesting quote of the parent and the participant's newly typed text. Robocop is not involved.

## Frontend interaction

- Every message footer exposes a Reply button on the left.
- Existing Edit/Delete controls remain grouped on the right.
- Reply is available for human, guest, and Robocop messages.
- Clicking Reply selects that message and gives it a dark-orange background.
- Clicking Reply again on the selected message cancels reply mode.
- Clicking Reply on another message transfers selection to the new message.
- Selecting or cancelling a reply does not alter the composer text.
- Sending or successfully cancelling through selection clears reply mode.
- Changing chats or reloading the page clears reply mode.
- Editing an existing reply loads only that reply's own body into the composer. The `.reply-quote` block is never exposed as editable text.
- Reply mode and Edit mode are mutually exclusive. Starting Edit clears the orange Reply selection. Reply buttons remain visible but disabled while editing, then become active again after Edit is completed or cancelled.

## Client-to-socket protocol

For an ordinary message, Vue continues to send the existing `message` event.

For a reply, Vue sends a `reply_message` event containing:

```json
{
  "version": 2,
  "channel": "current-channel",
  "type": "reply_message",
  "replyToMessageId": "123",
  "msg": "Newly typed reply text",
  "clientMessageId": "retry-safe-client-id",
  "CurrentUser": {}
}
```

The client sends only the parent ID and newly typed text. It does not construct or submit trusted quote markup, parent identity, or parent timestamp.

## Socket behavior

The socket accepts `reply_message` only after the same channel and admission checks used for ordinary messages.

It must:

1. Validate the new message text and `clientMessageId` using the existing message rules.
2. Resolve `replyToMessageId` as a positive message ID belonging to the joined chat.
3. Read the current stored parent message.
4. Remove the parent's existing canonical `.reply-quote` block, if present.
5. Take the first 150 characters of the parent's own remaining text using Unicode-aware character handling.
6. Construct one canonical quote block containing the parent author name, parent timestamp, and escaped excerpt.
7. Append the newly typed reply text below the quote block.
8. Store the result as one ordinary message from the replier using existing idempotency and pruning behavior.
9. Broadcast one ordinary `message` event.

If the parent does not exist in the current chat, the socket rejects the request and stores nothing. A retry with the same `clientMessageId` returns the existing stored message rather than creating a duplicate.

## Canonical stored representation

The reply is stored in `chat_message.message_text`; no migration or new relation is required. The canonical prefix is structurally equivalent to:

```html
<div class="reply-quote" data-timestamp="PARENT_UNIX_TIMESTAMP">
  <div class="reply-quote-author">ESCAPED_PARENT_AUTHOR</div>
  <div class="reply-quote-text">ESCAPED_150_CHARACTER_EXCERPT</div>
</div>
NEW_REPLY_TEXT
```

Only the socket creates this prefix. Parent-derived fields are escaped before storage. When replying to a reply, the socket removes the complete existing canonical prefix before calculating the new excerpt. This prevents nested quote chains.

To prevent a participant from forging a quoted-message header, ordinary message creation and message editing reject text that begins with the reserved canonical reply-block prefix. The reply body may contain similar text because only a canonical block at the absolute beginning is parsed as reply metadata.

The combined stored message must remain within the existing message-size limit. Because the parent excerpt is capped at 150 characters, the socket can account for the canonical prefix before accepting the newly typed text.

When editing a stored reply, the backend retains its canonical quote prefix unchanged and replaces only the reply body. The edited combined message must still fit the existing size limit. An edit cannot change the quoted parent, author, timestamp, or excerpt.

## Safe frontend rendering

Vue recognizes only a canonical reply block at the beginning of a message. It parses the author, timestamp, excerpt, and remaining message text into structured parts. It must not render the complete stored message with general-purpose `v-html`.

The quote fields render as escaped text. Links, media URLs, code fences, and markup inside the quote excerpt remain plain text. The new reply body continues through the existing `messageParts` rendering behavior.

The parent timestamp follows the existing chat display convention:

- Today: `HH:mm`.
- Earlier dates: `yyyy-MM-dd HH:mm`.
- Always a 24-hour clock.

## Styling

The quote block uses:

- a `3px` purple left border;
- gray text;
- `0.7rem` font size.

The selected parent message uses a dark-orange background until reply mode is cancelled, transferred, or the reply is successfully sent.

## Testing

Backend tests cover parent lookup scoped to the current chat, ordinary and nested-parent replies, Unicode 150-character excerpts, canonical escaping, missing parents, message-size validation, idempotent retries, persistence, pruning, and broadcast payloads.

Backend tests also cover rejection of a forged canonical prefix through ordinary message creation or editing.

Frontend contract tests cover Reply-button placement, selection toggling and transfer, unchanged composer text, reply transport payloads, selection clearing only after successful send, editing only the reply body, Edit/Reply mutual exclusion, safe canonical parsing, dynamic 24-hour timestamp formatting, quote styling, and dark-orange selected state.

The SPA build remains a separate user-run step.
