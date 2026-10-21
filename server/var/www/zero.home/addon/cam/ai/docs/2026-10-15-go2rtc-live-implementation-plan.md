# go2rtc Shared Live Video Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the per-viewer PHP/FFmpeg Live stream with an authenticated go2rtc MSE relay that shares one camera producer among concurrent viewers.

**Architecture:** PHP prepares a stable, server-side go2rtc stream from the private camera registry and issues a short-lived camera token. Nginx authorizes and proxies only the MSE WebSocket endpoint; the browser keeps the existing Live/PTZ/talkback UI while consuming fragmented MP4 from go2rtc.

**Tech Stack:** PHP 8.2, vanilla ES modules, Nginx, Docker Compose, go2rtc 1.9.14, RTSP, WebSocket, Media Source Extensions.

**Spec:** `server/var/www/zero.home/addon/cam/ai/docs/2026-10-15-go2rtc-live-design.md`

## Global Constraints

- Add go2rtc only to optional `dc.cam.yml`, which is included by `aaa/local` and `bbb`, never by `sss`.
- Pin `alexxit/go2rtc:1.9.14`; do not use `latest` or `master`.
- Keep `alina_cam` dedicated to talkback and name the new container `alina_go2rtc`.
- Never place camera URLs or credentials in committed YAML, JavaScript, browser responses, or logs.
- Expose no host port and proxy only the authenticated go2rtc MSE WebSocket path.
- Preserve Balcon UDP, Door TCP, existing PTZ, and existing talkback behavior.
- Retain the old PHP/FFmpeg streaming code as an unused rollback path.
- Do not run a JavaScript build; the add-on serves ES modules directly.
- Do not create a Git commit unless the user separately asks for one.

## Review Focus

- Expired, malformed, or camera-mismatched tokens must fail before the WebSocket upgrade.
- RTSP usernames/passwords containing reserved URL characters must be encoded and never returned to the browser.
- Repeated and concurrent preparation for the same camera must use one stable stream name and remain idempotent.
- An unsupported browser codec/MIME response must stop cleanly with a useful status and no reconnect loop.
- Missing or unhealthy go2rtc must affect only Live playback, not configuration, PTZ, or talkback APIs.

---

### Task 1: Relay source model and private go2rtc client

**Files:**
- Modify: `server/var/www/zero.home/addon/cam/stuff/php/LiveStream.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/Go2rtcClient.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/tests/LiveStream.test.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/Go2rtcClient.test.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/tests/run.php`

**Interfaces:**
- Produces: `LiveStream::relayName(string $cameraId): string`.
- Produces: `LiveStream::relaySource(string $cameraId): string`, including explicit `#transport=udp|tcp`.
- Produces: `Go2rtcClient::__construct(string $baseUrl = 'http://go2rtc:1984/cam-stream', ?Closure $request = null)`.
- Produces: `Go2rtcClient::upsert(string $name, string $source): void` using `PUT /api/streams`.

- [ ] **Step 1: Add failing relay-source tests**

Assert that Balcon produces a stable safe name plus an encoded RTSP URL ending in `#transport=udp`; Door produces TCP and DVRIP credential fallback. Assert that neither public metadata nor thrown errors include the fixture password.

- [ ] **Step 2: Run the focused PHP tests and confirm failure**

Run: `php server/var/www/zero.home/addon/cam/stuff/tests/run.php LiveStream Go2rtcClient`

Expected: FAIL because relay methods/client do not exist.

- [ ] **Step 3: Add `relayName()` and `relaySource()` without removing rollback methods**

Keep `probeCommand()` and `streamCommand()` intact. Reuse central camera validation and URL encoding; constrain relay names to a fixed application prefix plus the validated camera ID.

- [ ] **Step 4: Implement the private go2rtc client**

Send an idempotent URL-encoded PUT with a short connection/read timeout. Accept only a 2xx response. Convert transport, timeout, and non-2xx failures into a generic `RuntimeException` that contains no source URL.

- [ ] **Step 5: Run the focused PHP tests**

Expected: PASS, including reserved-character credentials, stable names, idempotent PUT shape, and sanitized error cases.

### Task 2: Stream preparation and WebSocket authorization

**Files:**
- Modify: `server/var/www/zero.home/addon/cam/stuff/php/live-stream.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/LiveAuthorization.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/live-auth.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/LiveAuthorization.test.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/tests/TalkToken.test.php`
- Modify: `server/var/www/zero.home/addon/cam/stuff/tests/run.php`

**Interfaces:**
- Consumes: `LiveStream::relayName()`, `LiveStream::relaySource()`, `Go2rtcClient::upsert()`, and `TalkToken::issue()/verify()`.
- Produces: `GET live-stream.php?action=prepare&camera=<id>` returning `{ok, stream, token, expiresAt}` only.
- Produces: `LiveAuthorization::authorize(array $query, int $now): void`.
- Produces: internal FastCGI endpoint `live-auth.php`, returning 204 or 401.

- [ ] **Step 1: Add failing authorization tests**

Cover a valid camera-bound token and stable stream name; reject expired, malformed, mismatched-camera, mismatched-stream, and unknown-camera input. Assert no camera credential appears in response data or errors.

- [ ] **Step 2: Run the focused PHP tests and confirm failure**

Run: `php server/var/www/zero.home/addon/cam/stuff/tests/run.php LiveAuthorization TalkToken`

Expected: FAIL because Live authorization is not implemented.

- [ ] **Step 3: Implement the preparation action**

Load the existing private camera config, register the relay source through `Go2rtcClient`, and issue a 30-second signed token using `ALINA_CAM_TALK_SECRET`. Do not probe or stream media in this action. Preserve the old `info` and `stream` actions solely for rollback.

- [ ] **Step 4: Implement the internal authorization endpoint**

Validate `camera`, `src`, and `token`; ensure the token camera and derived relay name match exactly. Return no response body and never contact the camera or go2rtc.

- [ ] **Step 5: Run the focused PHP tests**

Expected: PASS for all valid and rejection cases.

### Task 3: Optional go2rtc service and restricted Nginx proxy

**Files:**
- Create: `server/etc/go2rtc/go2rtc.yaml`
- Modify: `dc.cam.yml`
- Modify: `server/etc/nginx/conf.d/location.alina.php82`

**Interfaces:**
- Consumes: internal authorization endpoint from Task 2.
- Produces: Docker DNS service `go2rtc:1984` and same-origin endpoint `/cam-stream/api/ws`.

- [ ] **Step 1: Add the minimal hardened go2rtc configuration**

Enable API/WebSocket/RTSP-input/MP4 functionality, set API base path `/cam-stream`, and disable go2rtc's RTSP output and WebRTC listeners. Leave `streams` empty and store no credentials.

- [ ] **Step 2: Add `go2rtc` to `dc.cam.yml`**

Use pinned image `alexxit/go2rtc:1.9.14`, container name `alina_go2rtc`, `restart: unless-stopped`, `read_only: true`, `/tmp` tmpfs, `no-new-privileges`, dropped capabilities, a 256 MiB memory limit, only internal port 1984, the read-only config mount, and `alina_network`.

- [ ] **Step 3: Add the Nginx authorization subrequest**

Create an `internal` exact location that invokes `live-auth.php` through the existing PHP-FPM backend and forwards only the original `camera`, `src`, and `token` arguments.

- [ ] **Step 4: Add the exact WebSocket proxy location**

Proxy only `location = /cam-stream/api/ws` to `go2rtc:1984`, require the auth subrequest, preserve query arguments, configure Upgrade/Connection headers, disable buffering/cache, and use bounded connection plus long idle timeouts. Do not proxy `/cam-stream/`, the WebUI, config, or stream mutation APIs.

- [ ] **Step 5: Perform static configuration checks**

Run `docker compose -f dc.all.yml -f dc.all.php82.yml -f dc.cam.yml -f dc.all.signal.yml -f dc.dev.yml config --quiet` and an Nginx syntax check against the composed configuration. Expected: both exit 0; no service publishes go2rtc ports.

### Task 4: Browser MSE-over-WebSocket transport

**Files:**
- Modify: `server/var/www/zero.home/addon/cam/stuff/js/live-form.js`
- Modify: `server/var/www/zero.home/addon/cam/stuff/js/app.js`
- Modify: `server/var/www/zero.home/addon/cam/stuff/tests/ui.test.mjs`
- Modify: `server/var/www/zero.home/addon/cam/index.php`

**Interfaces:**
- Consumes: prepare JSON from Task 2 and `/cam-stream/api/ws` from Task 3.
- Produces: `renderLiveForm({prepareEndpoint, relaySocketPath, ...})` with the existing returned `{element, destroy}` contract.

- [ ] **Step 1: Add failing UI transport tests**

Use a fake WebSocket and MediaSource to assert preparation before connection, correct camera/src/token URL encoding, MSE codec negotiation, queued binary append order, autostart, Stop/destroy cleanup, background-tab retention, token/relay failures, unsupported MIME handling, and no HTTP streaming fetch.

- [ ] **Step 2: Run the focused UI tests and confirm failure**

Run: `node --test server/var/www/zero.home/addon/cam/stuff/tests/ui.test.mjs`

Expected: FAIL on the new WebSocket transport expectations.

- [ ] **Step 3: Replace only the media transport in `live-form.js`**

Prepare the stream, open the same-origin WebSocket, send the supported MSE codec list, accept go2rtc's MIME response, and append binary fragmented-MP4 messages through a single ordered queue. Preserve live-edge correction, bounded old-buffer removal, native video controls, initial mute, PTZ, and talkback.

- [ ] **Step 4: Wire the new endpoints and update cache versions**

Pass `./stuff/php/live-stream.php` as `prepareEndpoint` and `/cam-stream/api/ws` as `relaySocketPath`. Bump only directly affected ES-module query versions in `app.js` and `index.php`; do not run a build.

- [ ] **Step 5: Run focused UI and PHP tests**

Expected: UI tests pass; the camera PHP suite passes without opening camera connections.

### Task 5: Documentation and focused runtime handoff

**Files:**
- Modify: `server/var/www/zero.home/addon/cam/ai/docs/live-video.md`
- Modify: `server/var/www/zero.home/addon/cam/ai/docs/CURRENT_STATE.md`

**Interfaces:**
- Consumes: the completed runtime behavior from Tasks 1–4.
- Produces: operator documentation for composition, security, rollback, and concurrent-viewer verification.

- [ ] **Step 1: Replace the obsolete per-viewer architecture description**

Document the browser -> Nginx -> go2rtc -> camera path, one shared producer/many consumers, no long-lived PHP-FPM worker, per-camera transport, MSE codec limits, and retained PHP rollback endpoint.

- [ ] **Step 2: Document optional deployment scope and secure boundary**

Record that only `aaa/local` and `bbb` include `dc.cam.yml`; `sss` has only a dormant exact Nginx location. Explain that the WebUI/config/mutation API and host ports are not exposed.

- [ ] **Step 3: Run final non-build verification**

Run PHP syntax on changed PHP files, `node --check` on changed JavaScript, focused PHP/UI tests, `git diff --check`, and review `git diff --stat`. Do not run a JavaScript build and do not commit.

- [ ] **Step 4: Runtime verification with the user**

After the user starts/recreates the optional services, verify one Balcon viewer, one Door viewer, then two simultaneous viewers of the same camera. Inspect internal go2rtc statistics only long enough to confirm one producer/two consumers; do not change camera settings or move PTZ motors.
