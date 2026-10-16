# Browser-to-camera talkback protocol

Date verified: 2026-10-15

This document records the working Balcon talkback architecture and the DVRIP
behavior confirmed from the native CMS packet capture and a successful audible
browser microphone test. It is deliberately detailed because the word
"connection" can otherwise hide three different network relationships.

## The three network connections

One active talk session uses three distinct full-duplex connections:

| Connection | Opened by | Destination | Protocol | Purpose |
| --- | --- | --- | --- | --- |
| Browser talk connection | Browser | Nginx `/cam-talk`, proxied to `alina_cam:8081` | WebSocket over HTTPS (`wss`) | Carries authorization/state messages and binary microphone PCM |
| Camera control connection | `alina_cam` | Camera DVRIP port `34567` | Raw DVRIP over TCP | Authenticates, obtains the DVRIP session ID, and sends `OPTalk Start`/`Stop` |
| Camera media connection | `alina_cam` | Camera DVRIP port `34567` | Raw DVRIP over a second TCP socket | Claims the talk channel and carries G.711 A-law media packets |

The browser does **not** open either raw camera TCP socket. Normal browser
JavaScript cannot open an arbitrary TCP connection to port `34567`. The browser
opens one WebSocket to the server; `alina_cam` translates that WebSocket audio
into the camera's two-socket DVRIP protocol.

Both camera sockets exist concurrently while talkback is active. The media
socket is not opened for every word or browser audio frame.

```text
Browser
  microphone
      |
      | signed PCM, mono, 8 kHz, 16-bit (binary WebSocket messages)
      v
Nginx /cam-talk  ----WebSocket---->  alina_cam
                                         |
                                         | TCP socket 1: login + Start/Stop
                                         +-------------------------------> camera:34567
                                         |
                                         | TCP socket 2: Claim + A-law media
                                         +-------------------------------> camera:34567
```

Live video and camera-to-browser listening are separate from this diagram.
They continue to use RTSP -> FFmpeg -> fragmented MP4 -> browser MSE. Talkback
does not put video on the WebSocket.

## Complete opening sequence

1. The user presses the closed microphone button. Talkback never starts merely
   because the Live page was opened.
2. In that same click handler, the browser simultaneously requests microphone
   permission, constructs/resumes Web Audio, and requests a short-lived token
   from the PHP API with action `talk-token` and the selected camera ID. This
   ordering is required by mobile browser user-gesture rules.
3. The browser opens `wss://<current-host>/cam-talk?token=<token>`.
4. Nginx upgrades and proxies the WebSocket to the optional `alina_cam`
   container on internal port `8081`.
5. `alina_cam` accepts the browser from any Origin, verifies the camera-bound
   HMAC token, verifies that the camera is configured with a speaker, and
   obtains the per-camera exclusive talk lock. The absent Origin restriction
   is an explicit deployment decision for frontends served through arbitrary
   public domain names and reverse proxies.
6. `alina_cam` sends WebSocket state `ready`.
7. `alina_cam` opens the **control TCP socket** to camera port `34567`.
8. On the control socket it sends the normal DVRIP login request, message
   `1000`, and accepts the successful login response, message `1001`.
9. The login response supplies the DVRIP `SessionID`. The same numeric session
   ID is used in subsequent control and media packets.
10. `alina_cam` opens a second, independent **media TCP socket** to the same
    camera port. It does not log in again on this socket.
11. On the media socket it sends `OPTalk` / `Action: Claim`, DVRIP message
    `0x059A`, using the session ID obtained through the control socket.
12. The camera accepts the media claim with message `0x059B`, `Ret: 100`.
    Balcon replies with sequence number one greater than the Claim request.
13. On the control socket, `alina_cam` sends `OPTalk` / `Action: Start`, DVRIP
    message `0x0596`.
14. The camera accepts Start with message `0x0597`, `Ret: 100`.
15. Only after both Claim and Start succeed does `alina_cam` start its persistent
    FFmpeg converter and report WebSocket state `active`.
16. The already-authorized browser microphone supplies binary PCM through the
    open WebSocket.

The important distinction is that `Claim` and media use the second socket,
while `Start` and `Stop` use the authenticated control socket. A successful
`Claim` response alone does not mean the camera speaker has been enabled.

## Audio format and media framing

Browser capture requests one channel with echo cancellation, noise suppression,
and automatic gain control. An AudioWorklet resamples it to:

- signed linear PCM;
- mono;
- 8,000 samples per second;
- 16 bits per sample;
- 320 samples (40 ms) per browser frame.

The microphone source feeds the AudioWorklet, which feeds a gain node fixed at
zero and then the browser audio destination. The silent destination connection
is intentional: Web Audio processing is pull-driven, and mobile Chrome may
stop a detached worklet graph. Keeping the graph complete makes PCM processing
continue without playing the local microphone through the phone speaker.

The browser sends that PCM as binary WebSocket messages. Text messages and odd
or oversized binary frames are rejected.

One persistent FFmpeg process in `alina_cam` converts PCM to:

- `G711_ALAW` / `pcm_alaw`;
- mono;
- 8,000 samples per second;
- 8 bits per encoded sample.

Each camera media packet uses:

| Item | Value |
| --- | --- |
| DVRIP message | `0x0598` |
| DVRIP session | Session obtained by login on the control socket |
| DVRIP sequence | `0` for every observed CMS outbound media packet |
| Media prefix | `00 00 01 fa 0e 02 40 01` |
| Encoded audio | 320 G.711 A-law bytes |
| Payload size | 328 bytes: 8-byte prefix + 320-byte audio frame |
| Frame duration | 40 ms |

The media socket can also deliver camera talk media with message `0x0599`.
The service drains those packets to keep the duplex socket healthy, but the
talkback path does not record or expose them. Camera listening remains part of
the separate RTSP live stream.

## Closing sequence

Talkback closes when the user presses the active stop button, changes camera,
leaves/destroys the Live view, closes the browser connection, loses the
microphone, encounters a converter/camera error, or sends no microphone frames
for 60 seconds.

Cleanup proceeds as follows:

1. Browser microphone tracks and the AudioContext/AudioWorklet are stopped.
2. The browser WebSocket closes.
3. `alina_cam` stops and reaps the FFmpeg converter.
4. On the still-authenticated control socket, `alina_cam` sends `OPTalk` /
   `Action: Stop` with message `0x0596`.
5. The media socket and control socket are closed.
6. The per-camera talk lock is released.

If the Stop write itself fails, both TCP sockets are still closed as the
fallback cleanup. No microphone data or converted talk audio is stored.

## Why the first implementation was silent

The first implementation used one TCP socket for login, Claim, and media. The
camera returned `Ret: 100`, so the handshake appeared successful, and packet
counters proved that browser PCM was reaching FFmpeg and converted A-law bytes
were reaching the camera.

However, Balcon did not route those bytes to its speaker. Comparing the complete
native CMS capture showed the missing behavior:

- CMS kept an authenticated control socket open;
- CMS opened a second media socket and issued Claim there;
- CMS issued `OPTalk Start` on the control socket;
- only then did CMS transmit media on the second socket.

Reproducing that exact two-camera-socket lifecycle made a server-generated
2 kHz test tone audible. The subsequent browser microphone test also passed
audibly. This proves that neither microphone volume nor FFmpeg conversion was
the root problem; the missing control/media socket separation and Start action
were the cause.

## Security and operational boundaries

- Camera credentials stay in server-side configuration and never enter browser
  JavaScript or a browser-visible camera URL.
- `ALINA_CAM_TALK_SECRET` signs camera-bound tokens valid for 30 seconds and
  must be shared by PHP-FPM and `alina_cam`.
- Only one active talk owner per camera is allowed.
- The service accepts talkback only for cameras explicitly marked
  speaker-capable. Balcon is positive; Door is negative.
- The existing text-chat `/ws` service is unrelated and unchanged.
- The optional `alina_cam` service is enabled for `local` and `bbb`, not `sss`.
- The current implementation follows the capture-proven DVRIP behavior. It does
  not use an assumed ONVIF audio-backchannel implementation.

## Relevant implementation files

- Browser lifecycle: `stuff/js/talk-client.js`
- Browser PCM AudioWorklet: `stuff/js/mic-worklet.js`
- WebSocket authorization and ownership: `stuff/php/TalkWebSocket.php`
- FFmpeg conversion: `stuff/php/TalkAudioConverter.php`
- DVRIP two-socket lifecycle: `stuff/php/DvripTalkSession.php`
- DVRIP packet construction: `stuff/php/DvripTalkPacketCodec.php`
- Service entry point: `stuff/php/talk-server.php`
- Optional Compose service: repository root `dc.cam.yml`
- Nginx WebSocket route: `server/etc/nginx/conf.d/location.alina.php82`

