# Live video

The camera application provides an inline **ACCESS -> Live** page for each
configured camera whose RTSP stream uses a browser-supported H.264 or HEVC
codec. Balcon and Door are currently verified.

## Data path

Selected-camera RTSP -> FFmpeg in the PHP 8.2 container -> fragmented MP4 over HTTPS ->
the browser's native Media Source Extensions player.

FFmpeg copies the compressed video stream (`-c:v copy`); it does not decode or
re-encode it. When the camera exposes an audio stream, FFmpeg converts only that
audio from the camera's G.711 format to mono AAC-LC at 48 kHz/64 kbit for the
native browser player. Playback still starts muted; the user enables listening
with the player's own volume control. If the browser cannot accept the combined
video/AAC MIME type, the page selects its video-only candidate. Camera credentials remain in server-side
`config.php` and are never included in browser URLs or FFmpeg error output.

The endpoint probes the selected stream with FFprobe, reports the codec and
dimensions, and starts playback only when the browser confirms native support.
The current Balcon main stream is HEVC, so its playback depends on the browser
and host system supporting HEVC through Media Source Extensions. Door currently
reports H.264 Baseline at 1920 × 1080 through the deployed HTTPS probe.

## Runtime behaviour

- Live is the default page after a camera connects, and selected-camera playback
  starts automatically when the page is opened. Stop remains explicit, while Start
  allows a manual reconnect after a stop, timeout, or error.
- Leaving the page, changing camera, refreshing the form, or hiding the page
  aborts the browser request and terminates its FFmpeg child process.
- One viewer per camera is permitted at a time in this PHP runtime.
- The player follows the newest buffered data and removes old buffered media to
  keep delay and browser memory bounded.
- Playback waits for one second of media before its initial start. This prevents
  real-time-paced cameras such as Door from entering permanent decoder
  underflow after starting on the first tiny fragment.
- A stream has a server-side safety deadline just below the PHP/FastCGI timeout;
  press Start again if that deadline is reached.
- Unknown camera identifiers remain rejected by the central configuration.

The former workstation-only `127.0.0.1:18765` prototype and all of its files
were removed. Live video now runs through the installed FFmpeg and FFprobe
binaries in the PHP 8.2 container; it does not require a separate local helper.

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

FFprobe and FFmpeg use a 32 KiB probe and 100 ms analysis duration so stream
discovery does not add a long startup pause. The UDP stream additionally uses a
100 ms maximum demuxer delay and a 16-packet reorder queue. Video remains stream
copied, so this tuning adds no video encoder load. The browser corrects toward
the live edge whenever it falls more than 1.5 seconds behind, targeting about
300 ms behind the newest buffered media. The larger correction threshold avoids
repeated seeks that starve Chrome's audio/video decoder and leave it buffering.

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

Each active viewer occupies one PHP-FPM worker and one FFmpeg process. Video is
copied, while camera audio alone is transcoded to AAC, so CPU use should remain
modest. Each active talker additionally occupies one small FFmpeg process and
two DVRIP TCP sockets in `alina_cam`, but
the long-lived request still consumes worker capacity. This is suitable for the
application's small-admin usage model and should be revisited before supporting
many concurrent viewers.
