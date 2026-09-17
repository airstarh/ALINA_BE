# Persistent guest identities and two-person channel links

Implemented the user-approved design in the frontend and a small socket protocol extension. No database lookup or authentication mechanism was added to the socket.

- `GuestIdentity.js` generates `crypto.randomUUID()` once and stores it under `alina.chat.guestId`. Nicknames retain the existing `alina.chat.guestNickname` key. Storage is per website origin/browser profile; clearing storage creates another identity. Blocked storage falls back to a page-session identity.
- CurrentUser has `id = 0` and `guestId` when anonymous. Applying web identity responses preserves the browser's guest identity; registered users do not expose a guest UUID. Logging out restores the same browser guest UUID.
- The guest input defaults to `Guest <first eight UUID characters>` and retains existing nicknames. Edits remain limited to 25 characters. Storage events synchronize nickname changes to other tabs on the same origin, including updating socket presence.
- Guests display their edited nickname plus `(Guest <short ID>)`. Default names are not duplicated. The full UUID is used for identity and channel names; short display labels are not a uniqueness guarantee.
- The server accepts valid version-4 UUIDs for anonymous participants and deduplicates their tabs in presence. Messages carry the same stable guest participant ID. Older clients without guestId retain socket-number identities and names. Registered-user behavior is preserved.
- Clicking another participant's avatar opens a new tab using the router's deployment base and `?channel=...`. Own avatars do not create links. Registered IDs sort numerically; registered IDs precede guest UUIDs; guest UUIDs sort lexicographically. Channel names use full UUIDs and are independent of nicknames.
- These are separate rooms, not access-restricted private conversations. Anyone knowing a channel can join. Guest UUIDs are identifiers, not credentials. Existing claimed registered IDs also remain outside this change's authentication scope.

Validation: canonical channel/name tests, local persistence and singleton login/logout checks, PHP stable identity and multi-tab presence checks, existing last-50 history checks, Vue SFC/CSS syntax and git diff checks passed. The real deployed worker was not restarted, and an end-to-end deployment/browser check remains for the user's deployment.

Deploy both frontend and backend changes together. Activating the backend requires restarting the chat worker, which loses its existing in-memory history. No frontend build, commit, local/remote service restart or remote modification was performed by Codex.
