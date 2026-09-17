# Vue text chat

Approved scope: a routed `ALINA_FE/src/views/Chat/index.vue` at `/chat?channel=1`
using the reusable `src/components/Chat/index.vue`. Default channel is `1`.

The desktop layout has a participant sidebar and message panel. The sidebar
collapses on mobile. Participants show a public display name, avatar, Online or
Typing status. Logged-in users with several connections have one participant
entry. Guests are separate connections. Joining and leaving never add messages.

Preserve Ctrl+Enter and button sending, own-message highlighting, HTTP(S) links,
image/video previews, URL copying, recent history, and exponential reconnect.
Connection feedback is outside the message stream. Preserve unsent drafts when
reconnecting, and release sockets/timers on unmount or channel changes.

Protocol v2 distinguishes join, message, history, presence, typing, and error
events. The server sends history to the joining connection only and stores only
actual messages (50 per active channel, in memory). Message timestamps and IDs
come from the server. Presence aggregates connections; typing expires after six
seconds and stops on sending. Connections belong to one channel at a time.

Legacy PHP clients continue using the existing JSON message shape and receive
history and live messages without v2 presence events. Outbound identities contain
only public fields. Existing client-supplied identity semantics are retained;
this change does not introduce an authentication protocol.

No new dependencies, Git commits, or frontend builds. Verify PHP syntax, protocol
behavior, Vue SFC syntax, existing migration contracts, and diffs. The user has
authorized implementation and will review the completed changes.
