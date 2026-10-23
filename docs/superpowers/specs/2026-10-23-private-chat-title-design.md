# Private chat title design

## Intent

Give each newly created private text-chat room a readable title while keeping
its canonical channel ID and URL unchanged. The title format is:

```text
PRIVATE: Participant 1 & Participant 2
```

The names follow the same identity ordering already used to construct the
private channel ID. The rule applies to user/user, user/guest, and guest/guest
private rooms, using each participant's complete displayed name.

## Required behavior

- ALINA_FE calculates the proposed title from the two displayed names and orders
  those names with the same identity ordering already used by `privateChannel()`.
- Carry the proposed title in Vue Router history state, not in the URL, then send
  it separately in the socket `join` payload.
- ALINA_BE validates that the channel has the existing canonical private-channel
  format and that the proposed title is a trimmed `PRIVATE:` title of at most 200
  Unicode characters.
- When inserting a new private chat, store the generated value in
  `chat.chat_name_human` and keep `chat.channel_name_pseudo_id` unchanged.
- Generate the title only during successful initial creation.
- Never overwrite an existing chat title. Participants may rename it later
  without changing the channel ID or link.
- If no valid proposed title reaches the socket, retain the existing fallback:
  use the technical channel ID as the initial human name.
- Make no database-schema or route changes.

## Approaches considered

1. **Generate in Vue and pass separately through socket join — selected.** Vue
   already has both displayed names when the private link is activated. Router
   history state keeps the URL unchanged, and ALINA_BE uses the proposal only in
   the atomic insert path. Canonical identity ordering makes both participants
   propose the same title.
2. Resolve both names entirely in ALINA_BE. Rejected because it adds unnecessary
   identity lookup logic when Vue already has the exact displayed names.
3. Add private-participant columns to `chat`. Rejected as unnecessary schema and
   migration complexity for data already encoded by the canonical channel ID.

## Implementation and verification boundaries

- Add a focused private-title builder in ALINA_FE and a private-channel/title
  validator in ALINA_BE.
- Extend the socket join payload with optional `chatName`, and extend chat
  creation so it is supplied only to the insert path; existing-room reads remain
  unchanged.
- Add tests for canonical ordering, all identity pairings, invalid or missing
  title fallback, creation-only naming, and preservation of later manual renames.
- ALINA_FE will require `npm run build:all` and the long-running chat container
  will require a restart before manual testing; both operational actions remain
  separate from source implementation unless explicitly authorized.
