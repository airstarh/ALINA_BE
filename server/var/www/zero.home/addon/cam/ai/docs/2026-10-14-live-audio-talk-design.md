# Live camera audio and talkback design

Date: 2026-10-14

## Purpose

Extend the camera add-on's existing Live page with:

- incoming camera audio in the existing browser video player when the selected camera provides it;
- toggle-style browser microphone talkback for cameras with a speaker;
- an isolated optional camera media service that does not alter the existing text-chat WebSocket service.

The first positive implementation target is the Balcon camera. Door may provide incoming microphone audio, but it must not expose talkback because it has no speaker.

## Confirmed protocol facts

The CMS packet capture at `/tmp/balcon-talk.pcap` established the Balcon talk protocol rather than relying on assumptions:

- transport: DVRIP TCP on camera port `34567`;
- operation: `OPTalk` with `Action: Claim`;
- successful camera response: `Ret: 100`;
- codec: `G711_ALAW`;
- sample rate: `8000` Hz;
- sample size: 8 bits;
- channel: `0`;
- observed talk session: approximately 16.65 seconds;
- audio payload flowed in both directions;
- the CMS sent 320 encoded audio bytes per observed outbound media frame, representing 40 ms at 8 kHz G.711 A-law.

The capture is investigation material and must not be added to Git.

## Architecture

Video and incoming audio remain on the current HTTP/MSE live-stream path. Video is not moved to WebSocket.

Outgoing browser microphone audio uses a new, dedicated WebSocket service named `alina_cam`. The existing `alina_php82chat` container, its `/ws` route, and the text-chat implementation remain untouched.

The browser sends microphone PCM to `alina_cam`. A persistent FFmpeg conversion process produces mono 8 kHz G.711 A-law. The service authenticates to the selected camera and opens two simultaneous TCP sockets to DVRIP port `34567`: an authenticated control socket for `OPTalk Start/Stop`, and a separate media socket for `OPTalk Claim` and audio frames. The browser itself opens only the WebSocket to `alina_cam`; it never opens either camera socket. See [talkback-protocol.md](talkback-protocol.md) for the capture-confirmed lifecycle, packet IDs, framing, cleanup, and the silent one-socket failure mode.

## Incoming camera audio

The existing live-stream probe detects whether the selected RTSP stream contains an audio track.

When an audio track exists:

- copy the existing video stream without re-encoding;
- transcode only the camera audio to browser-compatible AAC;
- package video and audio in the existing fragmented MP4 response;
- advertise the corresponding MSE codec combination to the browser.

When audio is absent or audio conversion fails, preserve video-only playback. Audio failure must never make the Live page unusable.

Autoplay remains muted to comply with browser policy. Mute and volume are managed only by the native controls of the existing `<video>` element; the add-on does not add a duplicate listening control.

## Talkback behavior

The control appears in a compact row below the video only when the selected camera is configured or positively detected as speaker-capable.

- Closed state: `🎙`.
- First click: request microphone permission and begin connecting.
- Active state: a red `■`, shown only after the camera accepts `OPTalk`.
- Second click: close the talk channel.

Talkback is cleaned up when the user changes camera, leaves the Live page, loses the WebSocket, loses microphone access, or presses the active control.

The UI displays short state-specific messages for microphone denial, connection failure, unsupported camera, busy channel, or unexpected termination. It must not replace these with a misleading generic camera failure.

## Service boundaries

The implementation is split into focused responsibilities:

1. WebSocket connection, origin checking, and short-lived authorization.
2. Browser microphone capture and bounded PCM delivery.
3. Persistent FFmpeg input/output lifecycle.
4. DVRIP authentication and `OPTalk` lifecycle.
5. Capture-derived talk packet framing and sequencing.
6. Per-camera exclusive talk-session ownership.
7. Live-stream audio detection, AAC conversion, and video-only fallback.
8. Live-page UI state and deterministic cleanup.

No microphone or talk audio is recorded or stored. It exists only in bounded buffers while a session is active.

## Safety and resource limits

- Camera credentials stay server-side and are never sent to browser JavaScript.
- A short-lived signed authorization value is required to open a talk WebSocket.
- Only camera identifiers from the server configuration are accepted.
- The WebSocket verifies the expected same-origin request.
- At most one talk session may own a camera at a time.
- Input message size, frame size, queue length, connection timeout, and idle timeout are bounded.
- When conversion falls behind, stale queued audio is dropped rather than increasing latency without limit.
- Talkback does not automatically reconnect or restart the microphone.
- Failure or exit of FFmpeg closes the WebSocket, both DVRIP TCP sockets, browser microphone track, and per-camera lock.
- Closing the browser connection releases every associated resource even when a normal stop message was not received.

## Docker and Nginx integration

Add exactly one optional Compose file, `dc.cam.yml`, defining the `alina_cam` service. It uses the existing PHP 8.2 image in which FFmpeg and FFprobe are already installed.

Include `dc.cam.yml` in:

- `admin/at/local/docker.up.sh`;
- `admin/at/local/docker.down.sh`;
- `admin/at/bbb/docker.up.sh`;
- `admin/at/bbb/docker.down.sh`.

Do not include it in `sss` scripts.

Add a distinct same-origin WebSocket location to `server/etc/nginx/conf.d/location.alina.php82`. It must not reuse or modify the existing `/ws` text-chat route.

## Failure isolation

- No RTSP audio track: play video only.
- Incoming-audio conversion failure: stop the failing stream process and permit a video-only retry.
- Microphone permission denied: keep talk closed and preserve playback.
- DVRIP login or `OPTalk` rejected: return the button to its closed state and show the specific reason.
- Talk channel already owned: reject the new session with a clear busy message.
- WebSocket, FFmpeg, either camera socket, or browser lifecycle failure: close the whole talk session and release its lock.
- Door camera: allow incoming audio when functional, but never show a talk control.

## Verification

Automated verification covers:

- capture-derived DVRIP framing and sequence behavior;
- accepted and rejected `OPTalk` handshakes;
- exact G.711 A-law conversion command and process cleanup;
- message, frame, buffer, idle, and connection limits;
- per-camera exclusive ownership;
- cleanup on every termination path;
- camera capability-driven talk-button visibility;
- incoming audio mapping and video-only fallback;
- Live-page state transitions.

Positive integration verification uses Balcon for incoming audio and talkback. Door verifies incoming-audio fallback and the absence of the talk button. The add-on loads JavaScript modules directly and has no JavaScript build step.

## First-iteration limits

- Balcon is the only talkback-positive camera.
- No audio recording, history, mixing, echo cancellation service, WebRTC, or video-over-WebSocket is introduced.
- No attempt is made to infer speaker capability from an unverified API flag; configuration supplies the safe fallback when detection is not conclusive.
- The implementation follows the observed DVRIP behavior and does not attempt a speculative ONVIF backchannel.
