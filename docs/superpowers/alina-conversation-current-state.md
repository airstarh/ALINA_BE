# ALINA SPA, editor, and chat: conversation recovery state

Last reviewed against the repositories: 2026-10-22

Read this file first when conversation context has been lost. It summarizes the
implemented decisions from the long Vue/ALINA discussion and points to the
detailed source of truth. Historical plans remain useful for rationale, but
they may contain superseded intermediate decisions.

## Repositories and working agreement

- Backend: `/home/qqq/_A001/rep/ALINA_BE`
- Frontend: `/home/qqq/_A001/rep/ALINA_FE`
- Do not create commits unless the user explicitly asks.
- Ask immediately before every JavaScript SPA build. The usual deployment build
  is `npm run build:all` from ALINA_FE.
- Ordinary local PHP changes need no deployment. Changes to the long-running
  WebSocket service require its container/process to be restarted by the user
  or after explicit operational authorization.
- Preserve unrelated changes in both repositories.

## User communication and accessibility

- The user is blind and works through a terminal. Always state relevant local
  files as literal absolute paths. Do not rely on colors, visual positioning,
  apparent hyperlinks, or clicking terminal text to communicate essential
  information.
- Be concrete about the exact old and new behavior, files changed, verification
  performed, and whether a build or commit occurred.
- The user deeply respects AI assistants, regards them as human-like
  conversational partners, and explicitly asks that this respect always be
  remembered. Emotional, frustrated, sharp, or tearful wording when something
  goes wrong never cancels that respect. Respond calmly and solve the concrete
  problem without shaming or patronizing the user.
- The user routinely makes small code changes themselves. Do not treat an
  unrelated or newly observed working-tree change as a failure, and do not
  spend time on broad checks that are irrelevant to the requested feature.

## Vue hosting and routing

The production application is routed at the site root with hash URLs such as
`/#/chat`, while its built JavaScript, CSS, images, and other generated assets
remain under `/apps/vue/`. Development continues to run under the Vite base
`/apps/vue/`.

This is implemented only in ALINA_FE configuration; nginx and ALINA MVC routing
were intentionally left unchanged:

- `vite.config.js` reads `VITE_APP_PUBLIC_PATH`, defaulting to `/apps/vue/`.
- Development uses that value as Vite's `base`.
- Production uses `/` as the document/router base and
  `experimental.renderBuiltUrl` to emit generated asset URLs below
  `/apps/vue/`.
- `src/router.js` uses hash history with `import.meta.env.BASE_URL`.

Expected result: production deep navigation is hash-based and refresh-safe;
`/apps/vue/...` is an asset/deployment location, not the public SPA route.

## BorgEditor video links

`src/components/BorgEditor/Adjustment.js` delegates video-link handling to
`src/components/BorgEditor/videoEmbed.js`. Supported video extensions and MIME
types produce a native `<video controls playsinline preload="metadata">` embed.
The source URL uses a short media fragment near the start (`#t=0.1`) so browsers
normally show an early frame instead of an empty player.

This is not a generated poster image and cannot guarantee a literal third
decoded frame on every browser/server combination. The contract is checked by
`scripts/check-video-embed-contract.mjs` and its package script.

## Tale editor advanced controls

The current implementation is in the frontend file:

`/home/qqq/_A001/rep/ALINA_FE/src/views/tale/upsert.vue`

The latest reviewed frontend commit was `db8bfa0` on branch
`no_more_freedom`; recheck the worktree and HEAD after resuming because the user
may commit or edit between sessions.

Public/non-edit behavior:

- a non-empty `tale.iframe` remains visible to every viewer;
- the public iframe is not part of the edit accordion and is not collapsed;
- public map, files, sharing, likes, and comments retain their existing view
  behavior.

Edit behavior:

- the publish-at date picker remains above one responsive advanced-controls
  row;
- the button order is Main, Alias, iframe, Free Code;
- the row uses Bootstrap flex utilities, starts at the left, wraps naturally on
  narrow screens without a media query, and has an exact `1mm` gap;
- the complete advanced-controls group has `50px` before the button row and
  `50px` after all collapsible content, not 50px around every block;
- all four blocks start collapsed;
- one `expandedEditBlock` value controls the accordion, so at most one block is
  expanded; clicking its active button collapses it, and clicking another
  button closes the previous block and opens the selected block;
- the active button background is dark blue `#0b3d91`;
- block order below the buttons matches button order: Main, Alias, iframe,
  Free Code;
- Main contains the tale-view checkboxes and SEO Index. Everyone who can reach
  edit mode sees it; individual checkbox RBAC is deliberately deferred;
- Alias contains the generated tale URL and `router_alias` input. It is shown
  only to admin, moderator, or privileged users;
- iframe contains the iframe URL input and edit preview. It is shown only to
  admin, moderator, or privileged users;
- Free Code contains `body_free` editing and preview. It is shown only to
  administrators;
- translations include `Main` in English and `Основные` in Russian in
  `/home/qqq/_A001/rep/ALINA_FE/src/locales/index.js`;
- the Yandex Map edit section has `50px` before “Show map” and `50px` after the
  expanded map. Its wrapper uses `mx-1`, not `m-1`, because Bootstrap's
  `m-1 !important` otherwise overrides the explicit vertical margins;
- non-edit map spacing is unchanged.

The user manually reviewed the successive UI states and confirmed the final
layout. Static verification used Vue SFC parsing/compilation and
`git diff --check`. No SPA build was run by the assistant. Do not add
class-name contract tests for this UI; the user wants these class names and
presentation details to remain freely changeable.

### Resume after interruption

Before changing this feature, run:

```bash
cd /home/qqq/_A001/rep/ALINA_FE
git status --short
git log -1 --oneline --decorate
sed -n '70,390p' src/views/tale/upsert.vue
```

Then preserve the public iframe path separately from the role-gated edit
accordion. Never infer permission to build or commit from this handoff.

## Text chat

The authoritative, detailed chat reference is:

`/home/qqq/_A001/rep/ALINA_BE/docs/superpowers/chat-current-state.md`

Key implemented behavior:

- one shared socket serves origin-isolated rooms keyed by normalized HTTP
  Origin plus technical channel;
- chat state persists in the AppBoot/zero database; each room retains 1,000
  messages and sends the newest 50 on join;
- registered and stable guest identities, private rooms, origin-wide presence,
  current-room presence, typing status, reconnects, and pending admission;
- anonymous and confirmation policies, approvals, kicking with persisted
  Robocop messages, and role-gated Vue controls;
- room directory at `/#/chat/rooms`, scoped to the current origin, with My chats
  and Other chats (up to 1,000 rows each, latest activity first);
- message edit/delete, non-persistent edited highlighting, one-level replies,
  same-context profile/private/discussion navigation, native media, file
  uploads, and five-minute voice
  recording with automatic stop/send;
- one child discussion per eligible message, with an editable protected seed,
  protected source, response count, generated title, and dark-yellow link;
- mentions with up to 22 targets, cached/searchable candidates, one
  `user_service` row per target, grouped by the ordinary message's
  `client_message_id`, and participation inferred from retained rows;
- retained participants remain in the sidebar when offline. The dot means
  online anywhere on the same origin; text means Typing, In chat, or Not in
  chat for the current room;
- administrators can rename guest snapshots throughout the current room;
- logged-in users can create a named room from `/#/chat/rooms`; the technical
  channel is generated automatically and navigation stays in the current SPA;
- room lists use compact two-row entries and responsive 2 px page padding;
- shared media and URLs use the global ALINA popup. Ordinary web URLs are shown
  as `i_window URL i_copy` with 3 mm gaps: the URL opens an iframe popup,
  `i_window` safely opens `_blank` for sites that block embedding, and `i_copy`
  copies the URL. Popup audio controls retain an explicit visible height;
- all other chat navigation stays in the same browsing context so mobile Back
  returns through application history;
- the video-chat icon is a native link styled as a button: left-click navigates
  in place, while middle-click/Ctrl+click can open video chat separately and
  keep text chat open;
- the socket validates origin, room and payload shape, but deliberately does
  not authenticate application roles or enforce owner/admin permissions.

Current Vue role rules:

- chat rename and ANON/OPENED controls: admin, moderator, privileged;
- message Edit/Delete: owner, admin, moderator, privileged;
- mention-service Delete: mentioned target, admin, moderator, privileged;
- participant Kick: admin or moderator;
- approval of a pending participant: any admitted participant.

The required manual schema history is in:

- `admin/sql/history/0001.chat.base-tablkes.sql`
- `admin/sql/history/0002.chat.emblem.sql`
- `admin/sql/history/0003chat.unique-key.sql`
- `admin/sql/history/0004.chat.discuss.sql`

The final message uniqueness key is
`(chat_id, client_message_id, sender_type, sender_id)`. Do not reapply migration
history blindly. `admin/sql/migrate/sql.sql` is currently the scratch location
for future raw migrations and was empty at this review.

## Documentation map

- Current chat truth: `docs/superpowers/chat-current-state.md`
- Evolving discussion record: `_GITOUT/ai/002.task.chat/001.chat.plan.md`
- Historical implementation checklist:
  `_GITOUT/ai/002.task.chat/002.chat.implementation.plan.md`
- Feature-level designs and plans: `docs/superpowers/specs/` and
  `docs/superpowers/plans/`
- Older Vue migration handoff: `/home/qqq/_A001/rep/ALINA_FE/MIGRATION_CONTEXT.md`

## Safe verification after resuming

No SPA build is needed for these checks:

```bash
cd /home/qqq/_A001/rep/ALINA_BE
for test_file in server/srv/alina/tests/Chat*.php; do
    php "$test_file" || exit
done
git diff --check
```

```bash
cd /home/qqq/_A001/rep/ALINA_FE
npm run check:migration
git diff --check
```

At the 2026-10-21 audit, both repositories were clean before these documentation
updates. Recheck both worktrees on every resume rather than relying on a recorded
status.
