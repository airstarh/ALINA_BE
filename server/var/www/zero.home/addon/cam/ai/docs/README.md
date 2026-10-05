# IP camera investigation

This directory contains sanitized investigation notes for the local IP cameras.
Credentials must remain in `_GITOUT/addon/cam/creds.md` and must not be copied
into this directory, application source, command examples, or browser-side code.

## Safety rules

- Prefer passive or read-only protocol operations.
- Probe only a specifically selected camera; do not scan the whole subnet.
- Do not change network, user, encoding, imaging, PTZ, or event settings during
  discovery.
- Do not invoke reboot, reset, backup/restore, firmware, or factory-default
  operations.
- Treat an advertised capability as a claim until a read-only request verifies it.
- Record mutations separately and require explicit user confirmation before they
  are attempted.

## Investigations

- [Balcon camera](balcon-discovery.md)
- [Balcon vendor configuration inventory](balcon-vendor-config.md)
- [Balcon configuration SPA design](2026-10-05-cam-spa-design.md)
- [Balcon configuration SPA implementation plan](2026-10-05-cam-spa-implementation-plan.md)
- [Camera SPA verification](cam-spa-verification.md)
- [SMTP editor implementation plan](2026-10-05-smtp-editor-implementation-plan.md)
- [Five safe network editors plan](2026-10-05-five-network-editors-plan.md)
- [Second network-editor batch](2026-10-05-second-five-network-editors.md)
- [Ten-page network-editor batch](2026-10-05-ten-network-editors.md)
- [Ten general/camera/detection editors](2026-10-05-ten-general-camera-editors.md)

## PHP DVRIP request machinery

The application does not use cURL, HTTP, AJAX, Composer, or a third-party PHP
package to communicate with a camera. DVRIP is sent over a native PHP TCP
stream.

The exact call chain is:

1. `DvripClient::getConfig()`, `setConfig()`, `getSystemInfo()`, or
   `getSystemFunction()` selects the DVRIP command. Configuration writes use
   message 1040 and send the complete named branch.
2. Private `DvripClient::request()` is the central request function. It asks
   `DvripPacketCodec::encode()` to build the binary DVRIP header and JSON
   payload, writes the packet, then reads and validates the response.
3. `StreamDvripTransport::connect()` calls PHP's built-in
   `stream_socket_client("tcp://HOST:34567", ...)` to open the camera TCP
   connection.
4. `StreamDvripTransport::writeAll()` sends bytes with PHP's built-in
   `fwrite()`.
5. `StreamDvripTransport::readExact()` receives bytes with PHP's built-in
   `fread()`.
6. `StreamDvripTransport::close()` closes the socket with `fclose()`.

In short: `DvripClient::request()` performs a logical DVRIP request;
`stream_socket_client()`, `fwrite()`, and `fread()` are the underlying PHP
network machinery. These are standard PHP stream functions and require no
additional library or continuously running camera worker.

## First writable form: Email / SMTP

The application now exposes a dedicated `Email / SMTP` editor while retaining
the raw Network configuration view. Its PHP endpoint is intentionally limited
to `NetWork.NetEmail`; arbitrary configuration names cannot be submitted by the
browser.

Saving performs one serialized operation under the existing per-camera lock:

1. Read the latest complete `NetWork.NetEmail` branch from the camera.
2. Validate and merge the typed browser fields into that fresh object.
3. Preserve all fields the form does not own, including schedule and server
   metadata. A blank password preserves the current camera password.
4. Write the complete SMTP branch with DVRIP message 1040.
5. Read the branch again and require an exact match before reporting success.

The password is never returned to JavaScript. The browser receives only the
boolean `passwordConfigured` indicator.

## Additional writable network-service forms

Five more dedicated editors are available above the read-only configuration
menu: FTP, NTP/time synchronization, UPnP, PMS push service, and DAS service.
They use a server-side allow-listed schema; the browser cannot choose an
arbitrary DVRIP branch or field path. Each form validates scalar types and
ranges, preserves unowned branch fields and blank passwords, writes only its
named branch, and requires exact read-back before reporting success.

These five were selected as the first batch because they avoid the camera's
address, login, video encoding, recording, firmware, reboot, and factory-reset
settings. Loading a form is read-only. A camera mutation occurs only after the
user opens Review changes and presses Save changes.

A second five-form batch adds Multicast, PPPoE, RTSP server, serial network
bridge, and vendor cloud/NAT. Forms that can affect connectivity or existing
video clients show a prominent warning. The RTSP editor owns only the server
sub-branch and preserves the separate RTSP client configuration.

The third batch adds ten pages: DHCP, Wi-Fi, Wi-Fi signal thresholds, vendor
connection controls, online-upgrade policy, and five DDNS providers. Multiple
pages may safely target one array branch: each save starts from the camera's
latest complete array and changes only the selected provider's owned fields.

The fourth ten-page batch moves beyond networking: general preferences, locale
formats, defog, white-light control, overlay indicators, OSD logo, audio input
and output volume, tracking sensitivity, and basic human detection. Complex
detection regions, schedules, and event-routing structures remain preserved
but read-only.
