# Live Camera Audio and Talkback Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add browser playback of camera microphone audio and safe toggle-style Balcon talkback through an isolated `alina_cam` WebSocket service.

**Architecture:** Keep video and incoming audio on the existing HTTP/MSE path, copying video and transcoding only G.711 audio to AAC. Send browser microphone PCM through a dedicated WebSocket service, convert it with persistent FFmpeg to 8 kHz G.711 A-law, and frame it with the DVRIP `OPTalk` protocol proven by the CMS capture.

**Tech Stack:** PHP 8.2, FFmpeg/FFprobe, DVRIP TCP, Ratchet, ReactPHP, browser MediaSource, Web Audio AudioWorklet, native ES modules, Docker Compose, Nginx.

**Spec:** `server/var/www/zero.home/addon/cam/ai/docs/2026-10-14-live-audio-talk-design.md`

## Global Constraints

- Never create a Git commit unless the user explicitly asks for one.
- Do not run Docker or deployment actions as verification; provide the operational commands for the user.
- Do not add a JavaScript build system; the add-on loads native modules directly.
- Preserve the existing `/ws` text-chat route and `alina_php82chat` service unchanged.
- Add only `dc.cam.yml`; include it for local and `bbb`, never `sss`.
- Balcon is talkback-positive; Door must not expose talkback.
- Credentials remain server-side and audio is never recorded or stored.

## Review Focus

- A malformed or oversized WebSocket frame must close only that talk session and release its camera lock.
- FFmpeg exiting while the browser remains connected must close microphone transmission and return the UI to `🎙`.
- A camera with video but no usable audio must continue with video-only playback.
- Changing cameras or leaving Live while talking must stop the microphone, FFmpeg, socket, and camera session.
- Two clients targeting Balcon must produce one active talker and one explicit busy response.

---

### Task 1: Capture-derived DVRIP talk framing

**Files:**
- Create: `server/var/www/zero.home/addon/cam/stuff/php/DvripTalkPacketCodec.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/DvripTalkPacketCodec.test.php`

**Interfaces:**
- Produces: `DvripTalkPacketCodec::claim(int $session, int $sequence): string`
- Produces: `DvripTalkPacketCodec::audio(int $session, int $sequence, string $alaw): string`
- Produces: `DvripTalkPacketCodec::response(string $header, string $payload): array`

- [ ] Write tests asserting the claim contains `OPTalk`, `Claim`, `G711_ALAW`, 8-bit, 8000 Hz, channel 0, and message ID `0x059A` as observed in the capture.
- [ ] Write tests asserting an audio frame accepts exactly 320 A-law bytes, uses message ID `0x0598`, carries the captured eight-byte media prefix, and encodes the correct payload length.
- [ ] Write rejection tests for wrong media length, wrong response message/session/sequence, oversized payload, and camera `Ret` other than 100.
- [ ] Run `php server/var/www/zero.home/addon/cam/stuff/tests/run.php DvripTalkPacketCodec.test.php` and confirm the new tests fail.
- [ ] Implement the codec using binary-safe `pack`/`unpack`; do not read the pcap at runtime.
- [ ] Re-run the focused test and confirm it passes.

### Task 2: DVRIP talk-session lifecycle

**Files:**
- Create: `server/var/www/zero.home/addon/cam/stuff/php/DvripDuplexTransport.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/php/StreamDvripTransport.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/DvripTalkSession.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/DvripTalkSession.test.php`

**Interfaces:**
- Consumes: `DvripTalkPacketCodec` from Task 1 and the existing DVRIP login hashing/framing conventions.
- Produces: `DvripTalkSession::open(string $username, string $password): void`
- Produces: `DvripTalkSession::send(string $alaw320): void`, `stream()`, and `close(): void`

- [ ] Write a fake duplex transport test for login → `OPTalk Claim` → `Ret: 100` → audio frame → close.
- [ ] Add tests for login rejection, claim rejection, partial writes, camera EOF, incoming-packet draining, and idempotent close.
- [ ] Run the focused test and confirm failure before implementation.
- [ ] Add a narrow duplex transport interface exposing the connected stream without changing existing `DvripClient` behavior.
- [ ] Implement the session with bounded reads and writes; switch to nonblocking mode only after login and claim succeed.
- [ ] Re-run both talk tests and existing DVRIP tests.

### Task 3: Capability configuration and short-lived authorization

**Files:**
- Modify: `server/var/www/zero.home/addon/cam/config.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/php/Config.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/TalkToken.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/php/api.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/js/api.js`
- Modify: `server/var/www/zero.home/addon/cam/stuff/tests/Config.test.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/tests/Api.test.php`

**Interfaces:**
- Produces: public camera catalogue field `talkback: boolean`.
- Produces: POST `action=talk-token&camera=<id>` returning `{token, expiresAt}` only for speaker-capable cameras.
- Produces: `TalkToken::issue(string $cameraId, int $now): string` and `verify(string $token, int $now): string`.

- [ ] Add tests that Balcon reports talkback, Door does not, unsupported cameras cannot receive tokens, expired/tampered tokens fail, and a missing `ALINA_CAM_TALK_SECRET` fails closed.
- [ ] Run the focused Config/API tests and confirm failure.
- [ ] Add `talkback => ['speaker' => true]` only to Balcon and validate the optional structure.
- [ ] Implement a 30-second base64url HMAC-SHA256 token bound to the camera ID; source its key only from `ALINA_CAM_TALK_SECRET` shared through the existing Compose env file.
- [ ] Handle token issuance before creating a DVRIP `CameraService`, so requesting a token does not open a camera connection.
- [ ] Add `CameraApi::talkToken(cameraId)` and re-run the tests.

### Task 4: Incoming camera audio in the existing player

**Files:**
- Modify: `server/var/www/zero.home/addon/cam/stuff/php/LiveStream.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/php/live-stream.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/js/live-form.js`
- Modify: `server/var/www/zero.home/addon/cam/stuff/tests/LiveStream.test.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/tests/ui.test.mjs`

**Interfaces:**
- Produces: live info JSON with `video` plus `audio: {available, codec_name}`.
- Produces: stream query `audio=1|0`; audio mode maps `0:a:0?`, encodes AAC-LC mono at 48 kHz/64 kbit, and leaves video as stream copy.

- [ ] Add command tests for G.711 audio discovery, AAC conversion with copied H.264/HEVC video, and explicit video-only mode.
- [ ] Add UI tests for an MSE MIME candidate containing `mp4a.40.2`, native muted autoplay, unsupported-audio fallback, and video-only recovery after an audio stream failure.
- [ ] Run both focused suites and confirm failure.
- [ ] Expand FFprobe metadata to identify the first video and optional first audio stream.
- [ ] Implement audio/video FFmpeg commands without returning credentials or FFmpeg stderr to the browser.
- [ ] Update MSE selection and one-time video-only fallback while retaining native player mute/volume controls.
- [ ] Re-run focused PHP and UI tests.

### Task 5: Isolated camera WebSocket service

**Files:**
- Create: `server/var/www/zero.home/addon/cam/stuff/php/TalkAudioConverter.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/TalkSessionRegistry.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/TalkWebSocket.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/talk-server.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/TalkAudioConverter.test.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/TalkWebSocket.test.php`

**Interfaces:**
- Consumes: verified token, configured camera, `DvripTalkSession`, and browser signed 16-bit mono PCM.
- Produces: WebSocket text states `ready`, `active`, `busy`, `error`, `closed`; binary messages are bounded PCM only.

- [ ] Add tests for the exact FFmpeg command (`s16le` input; mono 8 kHz `alaw` output), 320-byte output framing, converter exit, and bounded queue behavior.
- [ ] Add handler tests for origin/token rejection, one owner per camera, invalid JSON, oversized binary input, unsupported camera, successful activation, idle timeout, abrupt disconnect, and complete cleanup.
- [ ] Run the focused tests and confirm failure.
- [ ] Implement `proc_open` with argument arrays and nonblocking stdin/stdout managed by the React loop; stderr goes to the camera service log without exposing credentials.
- [ ] Implement the registry and Ratchet handler with a single cleanup path that closes FFmpeg, microphone ingress, DVRIP, and ownership.
- [ ] Implement the port-8081 runner using `/srv/alina/vendor/autoload.php`; do not boot or reference chat classes.
- [ ] Re-run all camera PHP tests.

### Task 6: Browser microphone toggle

**Files:**
- Create: `server/var/www/zero.home/addon/cam/stuff/js/talk-client.js`
- Create: `server/var/www/zero.home/addon/cam/stuff/js/mic-worklet.js`
- Modify: `server/var/www/zero.home/addon/cam/stuff/js/live-form.js`
- Modify: `server/var/www/zero.home/addon/cam/stuff/js/app.js`
- Modify: `server/var/www/zero.home/addon/cam/stuff/css/app.css`
- Modify: `server/var/www/zero.home/addon/cam/index.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/tests/ui.test.mjs`

**Interfaces:**
- Consumes: catalogue `talkback`, `CameraApi::talkToken`, `/cam-talk`, and AudioWorklet PCM.
- Produces: closed `🎙` and active red `■` toggle below the video.

- [ ] Add UI tests for hidden Door control, visible Balcon control, permission denial, connecting state, activation only after server acknowledgement, second-click close, busy/error states, and cleanup on destroy/camera change/socket loss.
- [ ] Add tests that worklet messages are signed 16-bit mono PCM and stop after track teardown.
- [ ] Run the UI suite and confirm failure.
- [ ] Implement a focused talk client that requests permission only on click, obtains a token, opens the WebSocket, loads the worklet, and never auto-reconnects.
- [ ] Place the media row below the video, preserve native player audio controls, and use specific non-generic status text.
- [ ] Increment direct asset query versions in import/script links; do not introduce a build step.
- [ ] Re-run the UI suite.

### Task 7: Optional container and proxy integration

**Files:**
- Create: `dc.cam.yml`
- Modify: `admin/at/local/docker.up.sh`
- Modify: `admin/at/local/docker.down.sh`
- Modify: `admin/at/bbb/docker.up.sh`
- Modify: `admin/at/bbb/docker.down.sh`
- Modify: `server/etc/nginx/conf.d/location.alina.php82`

**Interfaces:**
- Produces: container `alina_cam`, internal port 8081, healthcheck, and `/cam-talk` WebSocket route. The current deployment intentionally accepts valid signed tokens from every Origin.

- [ ] Define `alina_cam` from the existing PHP 8.2 build with Xdebug disabled, 128 MiB limit, read-only code mounts, writable log mount, `/tmp` tmpfs, `ALINA_MODE=PROD`, existing env file, and the camera talk runner command.
- [ ] Add exactly `-f dc.cam.yml` to the four approved local/`bbb` up/down Compose invocations; make no `sss` change.
- [ ] Add `/cam-talk` proxy headers, buffering disabled, bounded connect timeout, and an idle timeout consistent with the talk server; keep `/ws` unchanged.
- [ ] Run `bash -n` on the four scripts and `git diff --check`; inspect Compose/Nginx text statically without starting or restarting containers.

### Task 8: Full safe verification and documentation

**Files:**
- Modify: `server/var/www/zero.home/addon/cam/ai/docs/live-video.md`
- Modify: `server/var/www/zero.home/addon/cam/ai/docs/CURRENT_STATE.md`

- [ ] Document incoming audio, native player controls, Balcon-only talkback, required `ALINA_CAM_TALK_SECRET`, `alina_cam`, `/cam-talk`, and all failure fallbacks.
- [ ] Run `php server/var/www/zero.home/addon/cam/stuff/tests/run.php`.
- [ ] Run `node --test server/var/www/zero.home/addon/cam/stuff/tests/ui.test.mjs`.
- [ ] Run `bash -n` on all four modified lifecycle scripts and `git diff --check` on every touched file.
- [ ] Verify the diff does not alter chat code, `/ws`, `sss`, camera credentials, or unrelated user changes.
- [ ] Provide—but do not execute—the exact user commands to configure the secret, recreate `alina_cam`, reload Nginx, check both health endpoints, and perform one Balcon listen/talk positive test followed by a Door no-talk-control check.
