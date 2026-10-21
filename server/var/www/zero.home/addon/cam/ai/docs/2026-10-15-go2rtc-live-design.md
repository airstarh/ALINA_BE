# go2rtc shared live-video design

## Goal

Replace the Live page's one-PHP-worker/one-FFmpeg-process-per-viewer data path
with a shared go2rtc relay. Multiple browser viewers of the same camera should
share one upstream camera connection while the existing PTZ and talkback paths
remain unchanged.

This optional runtime is enabled only where `dc.cam.yml` is included: the local
(`aaa`) and `bbb` profiles. The `sss` profile does not include that Compose file.
The shared Nginx location file may contain the dormant proxy location on `sss`;
without the optional container it has no effect unless somebody requests that
exact route.

## Runtime composition

`dc.cam.yml` will contain two independent services:

- `cam` / `alina_cam`: the existing PHP WebSocket service for DVRIP talkback;
- `go2rtc` / `alina_go2rtc`: pinned to `alexxit/go2rtc:1.9.14` and used only as
  the shared live-video relay.

go2rtc joins `alina_network`, exposes TCP port 1984 only to other containers,
has no host-published ports, uses a read-only filesystem, and has a bounded
memory allocation. Its RTSP output server and WebRTC listener are disabled.
Only its HTTP/WebSocket API required by this integration remains enabled.

The go2rtc configuration contains no camera credentials and no committed
camera URLs. Streams are registered at runtime by PHP from the existing ignored
`addon/cam/config.php` registry.

## Request and media flow

1. The browser opens **ACCESS -> Live**.
2. PHP validates the selected camera and constructs its RTSP URL entirely on
   the server. It first looks up the stable go2rtc stream name and reuses an
   existing stream unchanged. It sends `PUT /api/streams` only when that name
   is absent; preparation must never replace a stream underneath connected
   viewers. A per-camera process lock serializes simultaneous first creation.
3. PHP returns only public stream metadata, the non-secret stream name, and a
   short-lived camera-bound signed token.
4. The browser opens the same-origin WebSocket endpoint
   `/cam-stream/api/ws?src=<name>&token=<token>` and requests MSE playback.
5. Nginx performs a PHP authorization subrequest for the WebSocket handshake,
   then proxies the accepted connection to go2rtc.
6. go2rtc opens the camera RTSP producer on demand and fans its packets out to
   every connected browser consumer. Every tab/device has an independent MSE
   WebSocket consumer, while all of them reuse the same named producer. When
   the last consumer leaves, go2rtc may close the idle producer.

Balcon retains its configured UDP RTSP transport by appending go2rtc's
`#transport=udp` source option. Door retains TCP. Compressed H.264/HEVC video is
repackaged into fragmented MP4 without video transcoding. For MSE, go2rtc can
package the cameras' G.711 audio as browser-compatible FLAC, avoiding the
current per-viewer AAC encoder. Playback remains muted initially and the native
browser player controls listening volume.

## Browser integration

The existing custom Live layout, autostart, Stop/Start controls, PTZ panel, and
talkback control remain. Only its media transport changes from an HTTP
`fetch()` body produced by PHP/FFmpeg to go2rtc's `/api/ws` MSE protocol.

The client:

- obtains the prepared stream and signed token from PHP;
- sends the browser-supported codec list to go2rtc;
- uses go2rtc's returned MP4 MIME type to create the `SourceBuffer`;
- appends binary WebSocket messages as fragmented MP4;
- preserves bounded buffering and live-edge correction;
- closes the WebSocket on Stop, camera change, or view destruction;
- keeps playback connected while its tab is hidden, but cancels any active PTZ
  movement immediately;
- reports relay, authorization, codec, and camera failures in the existing Live
  status area.

The old `live-stream.php` FFmpeg streaming implementation remains temporarily
available in the repository as a rollback mechanism, but the normal Live UI no
longer calls its long-running `action=stream` response. Therefore live viewing
no longer occupies a PHP-FPM worker for the life of the stream.

## Security boundary

Nginx exposes only the exact browser WebSocket endpoint. It does not proxy the
go2rtc root WebUI, `/api/config`, `/api/streams`, or any mutation/debug endpoint.
This matters because go2rtc configuration and stream APIs can contain camera
credentials and powerful source definitions.

Each browser handshake requires a short-lived HMAC token bound to the selected
camera. Nginx validates it through an internal PHP authorization location before
upgrading the connection. Registration calls travel directly from PHP-FPM to
`go2rtc:1984` on the private Docker network. Camera credentials remain only in
the ignored PHP camera configuration and in go2rtc process memory.

The existing `ALINA_CAM_TALK_SECRET` is reused as the camera-service signing
secret so the deployment does not need a second secret. The token's purpose is
expanded from talkback-only to camera-media authorization; talkback's
speaker-capability check remains separate.

## Failure and rollback behaviour

- If go2rtc is absent or unhealthy, stream preparation returns a concise
  service-unavailable error; the rest of the camera configuration application,
  PTZ API, and talkback service remain usable.
- A camera connection failure affects only that named stream and is reported in
  the Live player.
- Reopening Live obtains a fresh token and retries registration, which also
  refreshes changed camera host, port, credentials, or RTSP transport.
- No automatic fallback starts the old per-viewer PHP stream. Silent fallback
  would conceal relay failures and restore the PHP-FPM exhaustion problem.
  Rollback is instead one small endpoint change in the Live-page wiring.

## Deployment and verification

No JavaScript build is required; this camera add-on uses directly served ES
modules. Deployment requires pulling the pinned go2rtc image and recreating the
optional Compose services plus Nginx after configuration changes.

Focused verification is limited to:

1. static PHP/JavaScript syntax and Nginx/Compose configuration checks;
2. go2rtc health and the restricted proxy boundary;
3. one positive Balcon playback and one positive Door playback;
4. two simultaneous browser viewers of one camera, confirming one upstream
   producer and two consumers in internal go2rtc stream statistics;
5. simultaneous same-camera and different-camera tabs, including a background
   tab, without one viewer stopping another;
6. Stop/page-leave cleanup and existing PTZ/talkback smoke checks.

No camera settings, motor positions, credentials, or recording settings are
changed by this migration.
