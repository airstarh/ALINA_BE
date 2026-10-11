# Life native-browser feasibility prototype

2026-10-09. Temporary experiment approved by the owner, not the production
Life/PTZ feature. No camera settings, presets or PTZ movement are changed.

## Run and use

The root page is `life-prototype.php`. Press Start to probe the selected
camera and play video through native MediaSource Extensions. No playback
library, WebRTC service, go2rtc or software decoder is involved.

The existing PHP application runtime does not have FFmpeg/FFprobe installed.
The workstation does, so a temporary loopback-only PHP development server was
started at **http://127.0.0.1:18765/life-prototype.php**:

```bash
php -S 127.0.0.1:18765 -t server/var/www/zero.home/addon/cam server/var/www/zero.home/addon/cam/stuff/dev/life-prototype-router.php
```

Its router serves only the prototype, its stream endpoint, JS and CSS; not
configuration/credential files or other addon APIs. No operational server
configuration or software installation was performed. This is accessible on
the workstation only, not a production/LAN deployment. The server remains
running for the owner's feedback; stop it with Ctrl+C in its terminal/session.

## Data path and limits

Camera RTSP → FFmpeg `-c:v copy` → fragmented MP4 over HTTP → native browser MSE.
The camera URL and credentials remain server-side. Audio is omitted.
Streams are limited to 90 seconds and one active stream per camera in this
runtime. Stop, page hiding and page closing abort the browser request. The PHP
endpoint terminates its FFmpeg child on disconnect/end/deadline.

Main stream only for this probe. RTSP port uses persisted app overrides or
554; explicit per-service credentials take priority over the shared camera
account. No unattended installation, recording or transcoding fallback.

Balcon currently provides HEVC Main, level 150, 1920×1080. HEVC native browser
support is checked at runtime; lack of support produces a visible status
message rather than changing camera codecs. Browser support and actual visual
latency still require the owner's feedback. Buffer distance is kept small,
but this does not prove a particular camera-to-screen latency.

## Narrow checks

- PHP and JavaScript syntax checks passed.
- Metadata endpoint returned HEVC Main / 1920×1080.
- ffprobe recognized the streamed MP4 as HEVC with `hvc1` tag, 1920×1080.
- A five-second HTTP read delivered status 200 and approximately 1.67 MB;
  curl's timeout was intentional to disconnect the bounded positive probe.
- Workstation FFmpeg requires RTSP `-timeout` rather than `-rw_timeout` for
  this input; FFprobe accepts the latter. This was corrected before hand-off.
- No full suite or SPA build. No browser rendering success is claimed yet.

Reference: [Native Media Source API](https://developer.mozilla.org/en-US/docs/Web/API/Media_Source_Extensions_API).
