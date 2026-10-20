# Chat Voice Recording Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add one-button voice recording to ALINA chat: first press starts recording, second press stops and sends it, and reaching five minutes stops and sends automatically.

**Architecture:** Record audio in the browser with `MediaRecorder`, upload the resulting named `File` through the existing `/FileUpload` flow, and send a normal persisted chat message containing an explicit `VOICE:` URL marker. The socket protocol and database remain unchanged. Message parsing recognizes that marker before ordinary media-link detection so an audio WebM is rendered as audio while an ordinary WebM link remains video.

**Tech Stack:** Vue 3, browser MediaDevices/MediaRecorder APIs, existing `AjaxAlina` upload service, existing WebSocket chat protocol, Node contract checks.

**Spec:** `docs/superpowers/specs/2026-10-20-chat-voice-recording-design.md`

## Global Constraints

- Do not create a Git commit.
- Do not run a JavaScript SPA build without fresh explicit permission.
- Do not change the database, migrations, backend upload endpoint, or WebSocket message schema.
- Do not send audio bytes or Base64 through the WebSocket.
- Limit each recording to exactly 300,000 ms and auto-send when that limit is reached.
- Keep the existing composer text unchanged before, during, and after voice recording.
- Offer recording only to authenticated chat users.
- Always release microphone tracks and clear timers after completion, cancellation, error, route change, or component unmount.
- Preserve unrelated working-tree changes.

## Review Focus

- A timeout and a user click occurring together must produce only one uploaded and sent message.
- Leaving the room after microphone permission is granted must stop all tracks and must not send a partial recording.
- `MediaRecorder` may deliver its final `dataavailable` event asynchronously after `stop()`; file creation must wait for the recorder's `stop` event.
- `VOICE:https://.../recording.webm` must render as audio, while an ordinary `https://.../video.webm` link must continue to render as video.
- If upload succeeds but WebSocket sending fails, show an error and do not mutate the composer or retry automatically, preventing duplicate messages.

---

## Task 1: Build and test an isolated browser voice-recorder helper

**Files:**

- Create: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatVoiceRecorder.js`
- Create: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-voice-recorder-contract.mjs`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/package.json`

- [ ] Write a failing contract check for format selection in this preference order:
  `audio/webm;codecs=opus` (`.webm`), `audio/ogg;codecs=opus` (`.ogg`), then `audio/mp4` (`.m4a`).
- [ ] Add failing checks that `start()` requests `{audio: true}`, enters recording state, and starts the recorder using the selected MIME type.
- [ ] Add failing checks proving both manual stop and the 300,000 ms timer complete through the same callback exactly once.
- [ ] Add failing checks proving `cancel()` and startup/error paths clear the timer, stop every media track, and never invoke the completion callback.
- [ ] Add a failing asynchronous-stop check proving the named `File` is created only after all final chunks have arrived and has the chosen extension and MIME type.
- [ ] Implement `chooseVoiceFormat(MediaRecorderClass)` and `createVoiceRecorder(options)` with injected browser primitives and timers so the contract runs without a real microphone.
- [ ] Make stop/completion idempotent across click, timeout, recorder events, and errors.
- [ ] Add a package script named `check:chat-voice-recorder` for the contract check.
- [ ] Run `npm run check:chat-voice-recorder` from `/home/qqq/_A001/rep/ALINA_FE` and confirm it passes.

## Task 2: Parse and render explicit voice-message links

**Files:**

- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/chatProtocol.js`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/components/Chat/ChatMessage.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/assets/css/chat.css`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-message-contract.mjs`

- [ ] Add failing parser checks for an exact standalone line `VOICE:https://...`, producing a structured `voice` part with the URL and no visible marker.
- [ ] Add failing checks that malformed/non-HTTP `VOICE:` text remains ordinary text and an unmarked `.webm` URL remains a video part.
- [ ] Implement voice-marker parsing before generic link and media-extension classification.
- [ ] Add a failing component contract check for an `<audio controls preload="none">` branch using the structured voice part.
- [ ] Render voice parts with a native audio player and add only the minimal class needed to fit the existing message layout.
- [ ] Run the existing chat-message contract check and confirm reply, edit, video, and link behavior still passes.

## Task 3: Reuse chat upload logic and integrate the microphone button

**Files:**

- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/views/Chat/index.vue`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/assets/css/chat.css`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/src/locales/index.js`
- Create: `/home/qqq/_A001/rep/ALINA_FE/scripts/check-chat-voice-ui-contract.mjs`
- Modify: `/home/qqq/_A001/rep/ALINA_FE/package.json`

- [ ] Add failing checks that a microphone button exists beside the attachment control, uses the project's localized one-symbol icon approach, and exposes localized start/stop tooltips.
- [ ] Add failing checks that guests and disconnected users cannot start recording, while an active recording can always be stopped by its second press.
- [ ] Extract the existing attachment upload body into `uploadChatFiles(files)` returning uploaded public URLs; keep ordinary attachment behavior unchanged.
- [ ] Initialize the recorder helper in the chat component. First press starts recording; while active, style the microphone control red and visibly active; second press calls the shared completion path.
- [ ] On completion, upload the generated `File`, then send exactly `VOICE:<uploaded-url>` through the existing normal chat-message send path without reading or changing the textarea.
- [ ] Disable conflicting attachment/send actions while voice upload or completion is in progress and prevent a second recording from starting.
- [ ] Surface unsupported-browser, permission, empty-recording, recording, upload, and socket-send failures through localized existing chat error UI.
- [ ] Cancel without sending on channel change and component unmount; ensure media tracks and the five-minute timer are released.
- [ ] Add a package script named `check:chat-voice-ui` and run it.

## Task 4: Integrated static verification

**Files:**

- Verify all files changed above.

- [ ] Run all relevant frontend contract scripts, including chat protocol, chat message, reply/edit, recorder, and voice UI checks.
- [ ] Run the project's non-build lint/static checks relevant to changed Vue and JavaScript files, if configured.
- [ ] Run `git diff --check` in both ALINA_FE and ALINA_BE.
- [ ] Review the final diff for accidental generated assets, backend/schema changes, unrelated edits, or composer-text mutations.
- [ ] Do not run `npm run build:all`; tell the user to run it after implementation, unless the user gives fresh permission immediately before the build.

## Manual Acceptance Checklist

- [ ] On HTTPS desktop and phone, first microphone press requests permission and starts recording.
- [ ] The active button is clearly red; its second press immediately stops, uploads, and sends one message.
- [ ] At five minutes, recording stops and sends once without another press.
- [ ] The voice message survives reload and plays for every participant through an audio player.
- [ ] Existing video links, including ordinary `.webm` links, still display as video.
- [ ] Existing composer text remains exactly as typed throughout voice recording and sending.
- [ ] Navigating away during recording turns off the microphone and sends nothing.
