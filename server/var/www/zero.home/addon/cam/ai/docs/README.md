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

- [Current implementation and hand-off state](CURRENT_STATE.md)
- [Live video through container FFmpeg and native browser playback](live-video.md)
- [Live PTZ control design](2026-10-14-live-ptz-design.md)
- [CMS selects: screenshot mapping and confirmed wire values](cms-select-mapping.md)
- [Access points: protocols, discovery and credentials](access-points.md)
- [JavaScript → PHP → raw DVRIP call map](DVRIP_CALL_MAP.md)
- [Balcon camera](balcon-discovery.md)
- [Door camera and automatic editor discovery](door-discovery.md)
- [Balcon vendor configuration inventory](balcon-vendor-config.md)
- [Human Detection feature discovery](human-detection-discovery.md)
- [Camera data storage options](storage-options.md)
- [Xiongmai Alarm Center and alarm-server settings](alarm-center.md)
- [Human Detection rule editor implementation plan](2026-10-07-rule-editor-plan.md)
- [Balcon configuration SPA design](2026-10-05-cam-spa-design.md)
- [Balcon configuration SPA implementation plan](2026-10-05-cam-spa-implementation-plan.md)
- [Camera SPA verification](cam-spa-verification.md)
- [SMTP editor implementation plan](2026-10-05-smtp-editor-implementation-plan.md)
- [Five safe network editors plan](2026-10-05-five-network-editors-plan.md)
- [Second network-editor batch](2026-10-05-second-five-network-editors.md)
- [Ten-page network-editor batch](2026-10-05-ten-network-editors.md)
- [Ten general/camera/detection editors](2026-10-05-ten-general-camera-editors.md)
- [Final 37-editor batch and completed scope](2026-10-05-final-thirty-seven-editors.md)

## PHP DVRIP request machinery

Configuration requests do not use cURL, HTTP, AJAX, Composer, or a third-party
PHP package to communicate with a camera. DVRIP is sent over a native PHP TCP
stream. The separate read-only still-image endpoint uses camera HTTP via PHP
cURL; browser-to-PHP requests use HTTPS/AJAX.

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

The header also provides a dedicated **Reboot camera** button after a camera is
selected. It requires browser confirmation and calls a POST-only API action.
The backend sends DVRIP message 1450 with `OPMachine.Action = Reboot`, then
keeps the control connection open briefly because this firmware may cancel a
reboot when the requesting client disconnects immediately.

## First writable form: Email / SMTP

The application now exposes a dedicated `Email / SMTP` editor while retaining
the raw Network configuration view. Its PHP endpoint is intentionally limited
to `NetWork.NetEmail`; arbitrary configuration names cannot be submitted by the
browser.

Saving performs one serialized operation under the existing per-camera lock:

1. Read the latest complete `NetWork.NetEmail` branch from the camera.
2. Validate and merge the typed browser fields into that fresh object.
3. Preserve all fields the form does not own, including schedule and server
   metadata. Editable password/key fields show the camera's current value; saving
   an empty field explicitly clears that camera value.
4. Write the complete SMTP branch with DVRIP message 1040.
5. Read the branch again and require an exact match before reporting success.

The separate **Test email** button checks `SupportMailTest`, reads the camera's
currently saved SMTP branch, and sends it through the camera's native
`OPMailTest` DVRIP action (message 1636). It does not save settings; a positive
camera response means the test was accepted, not that the recipient received
it. Unsaved form edits are intentionally excluded.

Dedicated editable forms return their current password/key values to JavaScript
and render them as visible text inputs by explicit homelab design. The raw
read-only explorer remains recursively redacted.

The Wi-Fi editor also mirrors the existing `NetWork.NetDHCP` `eth1` switch as
`Use DHCP (Wi-Fi / eth1)`. Static IP, subnet, and gateway values remain visible
but are disabled while DHCP is selected. Network type and channel use readable
selects, while the firmware-derived key-type code remains visible and locked.
The standalone DHCP Interfaces editor remains available and unchanged.
All Wi-Fi choices use human-readable labels; firmware strings, numeric values,
and control-byte enum codes remain internal. The locked password-format field
is labelled as either hexadecimal or ASCII rather than exposing its numeric
code. Disabled inputs and selects use a light-gray background throughout the
camera application.

## Additional writable network-service forms

Five more dedicated editors are available above the read-only configuration
menu: FTP, NTP/time synchronization, UPnP, PMS push service, and DAS service.
They use a server-side allow-listed schema; the browser cannot choose an
arbitrary DVRIP branch or field path. Each form validates scalar types and
ranges, preserves unowned branch fields, writes submitted passwords exactly,
writes only its named branch, and requires exact read-back before reporting
success.

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
Balcon's enabled Wi-Fi response alternates between raw control-byte enums and
misleading legacy strings. The editor normalizes the live-tested `q001` values
to the actual WPA2 Personal / AES radio modes and applies Balcon's required
inverse translation when saving. Unknown bytes remain readable `Code N` values.
A packet capture of the native CMS identified available-network search
as DVRIP message 1020 with name `WifiAP`; the form now provides Search, shows
the returned AP table, and can copy a selected AP into the editable fields.

The fourth ten-page batch moved beyond networking: general preferences, locale
formats, defog, white-light control, overlay indicators, OSD logo, audio input
and output volume, tracking sensitivity, and basic human detection. Human
Detection was subsequently expanded with the period schedule, alert actions,
and a Tripwire/Intrusion geometry editor. Other detection structures remain
preserved unless owned by a dedicated form.

The final 37-page batch completes the planned interface. Together with SMTP
and the first 30 schema-driven pages, the application originally exposed 68
dedicated editors. The later Wired network editor brings the current total to
69: one SMTP form plus 68 schema-driven forms. Status, capability,
definition-table, account, firmware, reboot,
factory-reset, and other action-oriented nodes remain available only where
appropriate in the read-only explorer; they are deliberately not generic
editable forms.

## Per-camera automatic discovery

The editor catalogue is now computed for the selected camera. The backend
reads capability/system information and probes each required configuration
family with getters only, then returns only editors whose branches and owned
field paths are valid. Shared top-level families are read once per discovery,
so repeated pages such as DDNS providers and alarm servers do not repeat DVRIP
requests. Missing/unsupported/device-error branches are omitted; transport
failures remain visible as connection errors.

Hardware capability checks suppress misleading template branches. In
particular, Door contains an inert `NetWork.Wifi` object but does not advertise
`NetWifi`, so its Wi-Fi pages are hidden automatically. Balcon continues to
show them. See [Door camera and automatic editor discovery](door-discovery.md).

The automatically discovered **Wired network** editor combines
`NetWork.NetCommon` with the `eth0` switch from `NetWork.NetDHCP`. It exposes
DHCP, static IP, subnet, gateway, and hostname on both Door and Balcon. MAC is
visible but is preserved server-side and cannot be changed by this form.
