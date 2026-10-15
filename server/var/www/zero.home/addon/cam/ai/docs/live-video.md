# Live video

The camera application provides an inline **ACCESS -> Live** page for each
configured camera whose RTSP stream uses a browser-supported H.264 or HEVC
codec. Balcon and Door are currently verified.

## Data path

Selected-camera RTSP -> go2rtc in the optional camera stack -> the exact
same-origin Nginx WebSocket endpoint -> the browser's native Media Source
Extensions player.

PHP performs only a short preparation request: it resolves the selected camera,
registers its private RTSP source with go2rtc, and returns a short-lived,
camera-bound authorization token. PHP-FPM does not carry video bytes and no
PHP worker remains occupied while the user watches. go2rtc owns one upstream
RTSP producer per active camera and shares it between all browser consumers.
Each browser receives fragmented MP4 over its own WebSocket connection.

The browser advertises only the MSE codecs it can decode and accepts only the
matching MIME type returned by go2rtc. Video remains in the camera's source
codec; it is not re-encoded. Playback starts muted, and the user enables
listening with the native player's own volume control. The current Balcon main
stream is HEVC, so playback depends on the browser and host system supporting
HEVC through Media Source Extensions. Door currently uses H.264 Baseline at
1920 × 1080. Camera credentials remain in server-side `config.php`; they are
sent only from PHP to go2rtc's private API and never appear in browser URLs.

### RTSP URL discovery versus the current dashboard URL

The protocol-standard way to discover a camera's exact RTSP stream addresses
is ONVIF, not DVRIP. The access probe performs this sequence:

1. Connect to the camera's configured or detected ONVIF device-service URL.
2. Call `GetServices` and select the service whose namespace is
   `http://www.onvif.org/ver10/media/wsdl`.
3. Call `GetProfiles` on that Media service.
4. For each returned profile token, call `GetStreamUri` with an
   `RTP-Unicast` stream setup and `RTSP` transport.
5. Accept only `rtsp://` or `rtsps://` addresses that still point to the same
   camera, and expose their profile-to-URL mapping on **ACCESS -> All points**.

This discovery is implemented in `stuff/php/AccessProbe.php`. DVRIP provides
related network configuration, capabilities and account management, but exact
stream-URI discovery comes from ONVIF `GetStreamUri`.

The Home dashboard and individual Live page currently do **not** consume the
discovered profile URLs. Both verified cameras accept the Xiongmai RTSP root,
so `stuff/php/LiveStream.php` constructs it from private configuration:

```text
rtsp://<username>:<password>@<camera-host>:<rtsp-port>/
```

It uses `access.credentials.rtsp` when explicitly configured, otherwise the
camera's DVRIP credentials; it uses `access.ports.rtsp` when configured,
otherwise port 554. `live.rtsp_transport` selects UDP or TCP. PHP registers
this server-side URL with go2rtc, while the browser receives only the safe
relay name and a short-lived token.

A future true auto-discovery path for Live would run the ONVIF sequence above,
choose the intended profile (normally the main stream), safely add server-side
credentials when the returned URI omits them, and register that discovered URI
with go2rtc. Until that selection policy is implemented, Live deliberately uses
the known working root rather than silently choosing an arbitrary ONVIF profile.

### Runtime responsibilities

| Component | Long-lived media responsibility |
| --- | --- |
| `alina_go2rtc` | Shares each camera's RTSP video and incoming microphone audio between browser viewers, then packages it as fragmented MP4 for MSE. |
| `alina_cam` | Carries outgoing browser microphone audio to a speaker-capable camera through the verified DVRIP `OPTalk` protocol. |
| PHP-FPM | Performs short configuration, authorization, PTZ, and stream-preparation requests only; it does not remain occupied by live media. |

The two media containers are deliberately independent. Closing or failing a
talkback session does not stop live viewing, and a go2rtc playback failure does
not alter camera configuration or the talkback service.

## Runtime behaviour

- Opening `/addon/cam/` lands on the multi-camera **Home** dashboard. It starts
  one compact player for every camera in `config.php`, while leaving the
  top-right selector on **Select a camera** and hiding the per-camera sidebar.
  The responsive card grid gives the available width to the configured
  cameras. **Borg Tech Service** in the page header is the Home link.
- Each Home card provides **Talk** only when the camera is explicitly marked
  speaker-capable, the shared server-side **RECORD** toggle, and **Go Live**.
  Go Live selects that camera and opens its full Live/configuration view.
  Returning Home closes only the dashboard browser playback and talkback
  sessions; an active server-side recording continues.
- Live is the default page after a camera connects, and selected-camera playback
  starts automatically when the page is opened. Stop remains explicit, while Start
  allows a manual reconnect after a stop, timeout, or error.
- Leaving the page, changing camera, refreshing the form, or pressing Stop
  closes that browser's WebSocket and releases its Media Source buffers.
- Hiding a browser tab keeps its live stream connected, so several tabs can
  continue watching simultaneously. Any active PTZ movement is still cancelled
  as soon as its tab becomes hidden.
- Multiple viewers may watch the same camera simultaneously. They have separate
  downstream WebSockets but share one upstream camera connection in go2rtc.
- The player follows the newest buffered data and removes old buffered media to
  keep delay and browser memory bounded.
- Playback waits for one second of media before its initial start. This prevents
  real-time-paced cameras such as Door from entering permanent decoder
  underflow after starting on the first tiny fragment.
- Unknown camera identifiers remain rejected by the central configuration.

## Manual server-side recording

The Live page shows a **RECORD** button below the native video player whenever
the recording endpoint is configured. The first press starts recording the
selected camera; the active red button and filename confirm the server-side
state. Pressing it again requests Stop. Recording is independent of the browser
player: stopping playback, navigating away, closing the tab, or opening another
viewer does not discard an active recording. Returning to Live reads the shared
per-camera state, and multiple tabs see the same active recording. Both Home
cards and individual Live views refresh this backend state every two seconds,
including while recording is inactive, so a recording started or stopped in a
different view is reflected by the button's active styling.

Recording settings are private top-level `config.php` values:

```php
'record' => [
    'storage' => '/var/www/zero.home/_GITOUT/cam',
    'lengthMax' => '300',
],
```

`storage` must be an existing absolute directory writable by PHP-FPM. Missing
storage disables only recording attempts; it does not prevent the rest of the
camera application from loading. `lengthMax` accepts a non-negative integer or
decimal integer string. If omitted it defaults to 60 seconds. A positive value
is passed to go2rtc as the exact recording duration. Zero omits the go2rtc
duration and means manual Stop, container termination, or the existing PHP-FPM
four-hour request safety limit ends the recording; it is not a durable recording
daemon.

PHP registers the same private camera relay used by Live playback, opens
go2rtc's internal `/cam-stream/api/stream.mp4` output with `mp4=flac`, and copies
the fragmented MP4 bytes directly to storage. Video is not re-encoded and no
additional RTSP connection is opened for another FFmpeg process. One recording
consumer is added to the shared producer and one PHP-FPM worker remains occupied
for the recording's lifetime. Incoming camera microphone audio is included when
available; outgoing browser talkback audio from `alina_cam` is not mixed into
the file.

Files use the configured camera ID and server-local start time:

```text
2026-12-31---22-55---balcon.mp4
```

The recorder writes `*.mp4.part` first and atomically renames it after a
non-empty stream closes. An immediate Stop before any bytes arrive removes the
empty partial file. If the same camera starts more than once in one minute,
later files receive `---2`, `---3`, and so on rather than overwriting an earlier
recording. Only one recording may own a camera at a time, but different cameras
may record concurrently. Storage and filenames always come from trusted server
configuration and validated camera IDs; the browser cannot submit either path.

The former workstation-only `127.0.0.1:18765` prototype and all of its files
were removed. The older PHP/FFmpeg streaming actions remain temporarily in
`live-stream.php` as a rollback path, but the normal Live page does not call
them. It uses the go2rtc relay exclusively.

## Latency tuning

RTSP transport is selected per camera in its private `config.php` entry:

```php
'live' => [
    'rtsp_transport' => 'udp', // or 'tcp'; TCP is the default
],
```

Balcon uses UDP to avoid TCP retransmission delays on the reliable local
network. Cameras without this setting, including Door, retain the safer TCP
default. UDP may show a damaged or skipped frame during packet loss, while TCP
waits to recover lost data and can therefore accumulate more latency.

The private go2rtc source URL carries each camera's configured RTSP transport.
The browser corrects toward the live edge whenever it falls more than 1.5
seconds behind, targeting about 300 ms behind the newest buffered media. The
larger correction threshold avoids repeated seeks that starve Chrome's
audio/video decoder and leave it buffering.

Balcon's main-stream GOP is set to 6 at 20 frames per second, placing an
intra-frame approximately every 300 ms. This shortens initial picture and
recovery delay at the cost of a modest bit-rate increase. The GOP is a camera
setting rather than an FFmpeg re-encode option.

Do not add FFmpeg's `nobuffer` input flag to this fragmented-MP4 pipeline. A
positive runtime comparison against Balcon showed that `+genpts+nobuffer`
suppressed the copied HEVC video frames, while `+genpts` alone delivered both
HEVC video and AAC audio successfully.

## Camera audio and talkback

Balcon is explicitly configured as speaker-capable. Its Live page therefore
shows one talk toggle below the video: `🎙` opens the channel after a click and
the active red `■` closes it. Microphone permission is never requested on page
load. Door can provide incoming RTSP audio, but it has no speaker and receives
no talk control.

Outgoing microphone audio uses the optional dedicated `alina_cam` container and
same-origin `/cam-talk` WebSocket. It does not reuse the text-chat `/ws` service.
The browser resamples mono microphone input to signed 16-bit 8 kHz PCM; a
persistent FFmpeg process converts it to G.711 A-law, and the service sends
captured 320-byte DVRIP `OPTalk` frames to the camera. Audio is neither recorded
nor stored. Only one browser may own a camera's talk channel at once, idle talk
sessions close after 60 seconds without microphone frames, and leaving Live or
changing camera closes the microphone, WebSocket, FFmpeg, both camera TCP
sockets, and lock.

The browser opens one WebSocket to `alina_cam`; it never connects directly to
the camera's DVRIP port. For each active talk session, `alina_cam` opens two TCP
sockets to camera port `34567`: an authenticated control socket for `OPTalk
Start/Stop`, and a media socket for `OPTalk Claim` plus G.711 A-law frames. Both
remain open concurrently until talkback stops. See the detailed
[talkback protocol and two-socket lifecycle](talkback-protocol.md).

Both PHP-FPM and `alina_cam` must receive the same private
`ALINA_CAM_TALK_SECRET` (at least 32 bytes) through `_GITOUT/env/.env`. API tokens
are camera-bound HMAC-SHA256 values valid for 30 seconds. The WebSocket also
requires an exact same-origin Host/Origin match.

### Why talkback remains in `alina_cam`

go2rtc supports browser two-way audio in principle, including a DVRIP
backchannel, but it is not a drop-in replacement for this verified Balcon
implementation. Browser microphone input in go2rtc uses WebRTC; the current
camera integration intentionally enables MSE playback only and disables the
go2rtc WebRTC listener. A migration would therefore require enabling and
securing WebRTC signaling and ICE networking, adding a credential-bearing
`dvrip://...?...backchannel=1` source, and replacing the current browser
microphone client.

More importantly, the pinned go2rtc 1.9.14 DVRIP backchannel owns one TCP
connection and sends `OPTalk Claim`, `OPTalk Start`, and audio data through that
client. Packet capture and positive speaker tests established that Balcon needs
the two concurrent connections described above: a control connection for
Start/Stop and a distinct media connection for Claim/audio. A standard go2rtc
backchannel has therefore not been shown to be protocol-equivalent for this
camera and could fail silently even though generic DVRIP talkback is supported.

The project decision is to retain `alina_cam`. It is a small, bounded, optional
service, starts FFmpeg only for an active talker, and already implements the
camera-specific sequence reliably. Consolidation into go2rtc should be
reconsidered only after an isolated WebRTC/DVRIP experiment proves audible
Balcon output without changing or removing the working fallback. If that test
requires a custom go2rtc fork, retaining `alina_cam` is preferred to carrying
the fork.

Upstream references used for this decision:

- [go2rtc two-way-audio capabilities](https://github.com/AlexxIT/go2rtc#two-way-audio)
- [go2rtc DVRIP configuration and backchannel](https://github.com/AlexxIT/go2rtc/blob/master/internal/dvrip/README.md)
- [pinned v1.9.14 DVRIP client sequence](https://github.com/AlexxIT/go2rtc/blob/v1.9.14/pkg/dvrip/client.go)
- [pinned v1.9.14 DVRIP audio framing](https://github.com/AlexxIT/go2rtc/blob/v1.9.14/pkg/dvrip/backchannel.go)

## Live PTZ controls

When the selected camera reports
`SystemFunction.OtherFunction.SupportPTZDirectionControl=true`, the Live page
shows eight physical pan/tilt directions, zoom in/out, and Low, Medium, and
High speeds beside the video. Door reports no such capability, so it receives
an explanation instead of actionable controls. Mirror and Flip image settings
do not reverse the commands: the arrows refer to the camera's physical axes.

Holding a movement control sends serialized 180 ms ONVIF `ContinuousMove`
pulses. Each backend pulse attempts `Stop` from a `finally` path, even if the
move fails or the browser disconnects. Releasing/cancelling the pointer,
pressing the central Stop button, changing/hiding the page, blurring the
window, or destroying the view prevents another pulse. If the camera reports
that Stop itself failed, all movement buttons are disabled until the page is
reloaded. Live video continues independently when a PTZ request fails.

Balcon also advertises preset mutation, so the page accepts a numeric slot for:

- **Go to** — recalls the slot without confirmation;
- **Save current position** — confirms before overwriting the slot;
- **Delete preset** — confirms before deleting the slot.

Preset actions use vendor DVRIP `OPPTZControl` message 1400 with
`GotoPreset`, `SetPreset`, or `ClearPreset`. This firmware returns a generic
127-entry ONVIF preset list even on static Door, so the page deliberately does
not list or claim that any numeric slot exists. Use only slots intentionally
created and tested by the operator.

### Physical verification checklist

Automated tests never move the motors. While watching Live, the owner should:

1. Select Low speed and briefly press at least two opposite directions; verify
   physical movement and release-stop behavior.
2. Briefly test Zoom + and Zoom −, then return to the desired balcony view.
3. Use **Go to** for known slots 1–5 and confirm the expected saved views.
4. Save a new expendable slot, move away, recall it, then delete that slot.
5. If any movement continues after release, use the central Stop control and
   leave the Live page; do not issue another command until the cause is known.

## Resource implications

Each active camera occupies one upstream RTSP connection in go2rtc. Additional
viewers add a WebSocket and fragmented-MP4 muxing work, but they do not add a
PHP-FPM worker or another camera connection. Each active talker still occupies
one small FFmpeg process and two DVRIP TCP sockets in `alina_cam`. Each active
manual recording adds one go2rtc MP4 consumer, one PHP-FPM worker, and the
camera bit rate in disk writes, without video transcoding.

## Optional deployment and security boundary

`dc.cam.yml` contains both `alina_cam` and `alina_go2rtc`. The local (`aaa`) and
`bbb` Docker start/stop scripts include this optional Compose file. The `sss`
scripts do not include it. The shared Nginx file still contains the exact
`/cam-stream/api/ws` location on `sss`, but it is dormant without go2rtc and the
camera application.

go2rtc publishes no host port. Nginx exposes only the exact MSE WebSocket path;
the go2rtc WebUI, configuration endpoint, and stream-mutation API remain
available only inside the Docker network. Every WebSocket upgrade first passes
through the internal PHP authorization endpoint, which verifies the camera,
relay name, signature, and expiry. The browser cannot register a source or
supply an arbitrary RTSP URL. The tracked base configuration is mounted
read-only; at startup the container copies it into a private `/config` tmpfs so
runtime stream registrations, including credential-bearing source URLs, never
write back to the repository or survive a container recreation.

For rollback, remove `dc.cam.yml` from the applicable Docker composition and
restore the Live page to the retained PHP `info`/`stream` actions. Do not expose
go2rtc port 1984 directly.
