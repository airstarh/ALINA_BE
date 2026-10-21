# Balcon camera configuration SPA design

Date: 2026-10-05
Status: implemented and verified read-only

## Purpose

Build a self-contained, build-free single-page application for exploring the
Balcon IP camera's vendor configuration from a modern browser. The first
version is strictly read-only. It replaces the discovery and inspection portion
of the legacy Windows CMS without duplicating MotionEye's everyday viewing and
recording functions.

The application belongs entirely under
`server/var/www/zero.home/addon/cam`. It does not use or modify the ALINA API in
`server/srv/alina` and introduces no Composer, npm, or system-wide dependency.

## Scope

Version one will:

- List configured cameras by safe display name; initially only Balcon exists.
- Select Balcon by default and connect after the user presses a button.
- Connect server-side to the selected camera using DVRIP over TCP port 34567.
- Display device identity, connection status, and read-only configuration
  branches.
- Present nested settings in a readable expandable tree.
- Allow refreshing one section or the complete dashboard.
- Report timeouts, malformed replies, unsupported branches, and connection
  failures without breaking the page.

Version one will not:

- Accept or display credentials in the browser.
- Send DVRIP setter or operational commands.
- Change camera settings, users, network configuration, PTZ position, alarms,
  firmware, time, recording state, or storage.
- Provide RTSP video playback, recording, or MotionEye functionality.
- Add support for Door or assume it behaves like Balcon.

## Directory layout

```text
server/var/www/zero.home/addon/cam/
├── index.php
└── stuff/
    ├── css/
    │   └── app.css
    ├── js/
    │   └── app.js
    └── php/
        ├── api.php
        ├── Config.php
        ├── DvripClient.php
        ├── DvripException.php
        ├── DvripPacketCodec.php
        ├── DvripReader.php
        ├── DvripTransport.php
        ├── StreamDvripTransport.php
        └── CameraService.php

_GITOUT/addon/cam/
└── config.php
```

The existing empty `index.html` will be replaced by `index.php` so the
directory has one unambiguous entry point.

Implementation adds `stuff/js/state.js` for request-generation tracking and a
dependency-free test suite under `stuff/tests`. The additional small PHP files
separate packet encoding, transport I/O, and typed failures so no raw command
sender needs to be public.

## Private configuration

`_GITOUT/addon/cam/config.php` is the only source of camera endpoints and
credentials. It is ignored by Git and located outside the web root. The file
returns an associative array keyed by stable camera ID. Initially it contains
only `balcon`.

Each camera entry may contain a safe display name, host, and protocol-specific
settings. DVRIP, ONVIF, and RTSP credentials remain separate because they are
not guaranteed to match. Version one consumes only the DVRIP settings.

The application locates this file from the repository root derived from its
own directory. Missing files, invalid arrays, absent required keys, invalid
ports, and invalid camera IDs produce controlled API errors. Configuration
values and resolved credential-bearing URLs are never returned to JavaScript.

Adding another camera to the private array later will make its safe ID and name
appear in the selector without requiring a JavaScript change. This design does
not add or investigate another camera now.

## Browser interaction

On startup, the SPA requests the camera list. The API returns only stable IDs,
display names, and the default camera ID. Balcon is preselected.

The page initially shows:

- A camera select box.
- Balcon selected by default.
- A Connect button.
- A neutral disconnected state.

After Connect, JavaScript submits only the selected camera ID. PHP resolves the
private entry and performs all authentication under the hood. A successful
connection opens the dashboard. The camera selection is remembered only for
the current page session; credentials and configuration payloads are not placed
in persistent browser storage.

The dashboard contains device summary information and navigation for:

- General
- Network
- Camera/image controls
- Detection
- Encoding
- Recording
- Storage
- Alarms
- UART, RS-485, and PTZ configuration
- OSD and volume
- Capabilities

Sections are loaded on demand to keep individual requests small. Nested arrays
and objects appear in an expandable tree with clear handling for booleans,
numbers, strings, nulls, empty collections, and unsupported sections. The UI
contains no editing controls in version one.

## HTTP API

The browser communicates with one JSON dispatcher at `stuff/php/api.php`.
Initial actions are:

| Action | Parameters | Purpose |
|---|---|---|
| `cameras` | none | Return safe camera IDs/names and the default ID |
| `status` | `camera` | Connect and return sanitized identity/firmware data |
| `sections` | `camera` | Return the allow-listed section catalogue |
| `config` | `camera`, `section` | Retrieve one allow-listed DVRIP branch |
| `config` | `camera`, `section=all` | Retrieve all safe branches independently |

Requests use GET because every operation is read-only. Responses use a stable
envelope:

```json
{
  "ok": true,
  "data": {},
  "error": null
}
```

Errors use `ok: false`, a machine-readable error code, and a concise message.
They do not include passwords, raw credential-bearing URIs, stack traces, or
complete packet dumps.

The backend accepts only known action names, configured camera IDs, and
allow-listed section IDs. Browser input never becomes a raw DVRIP command.

## PHP components

### `Config.php`

Loads and validates the ignored private configuration. It exposes safe camera
metadata separately from private connection parameters. No global mutable
configuration is required.

### `DvripClient.php`

Implements the minimum native PHP DVRIP transport required by version one:

- TCP connection with bounded connect/read/write timeouts.
- DVRIP packet header encoding and decoding.
- Login and logout/session handling.
- JSON payload encoding and decoding.
- Read-only configuration getter commands.
- Response length and session validation.
- Deterministic socket cleanup.

The class will not implement a generic public command sender. Its public API
exposes only login, safe getters, and close. Setter, reboot, upgrade, PTZ,
alarm-subscription, and media commands are intentionally absent.

### `CameraService.php`

Maps stable UI section IDs to exact DVRIP getter names. It combines identity
queries, normalizes protocol errors, and sanitizes response data before it
reaches the API layer. Each `section=all` branch is isolated so one unsupported
branch does not discard successful results from the others.

### `api.php`

Validates request parameters, invokes the config loader and service, maps
exceptions to HTTP status codes, and emits the JSON envelope. It sends explicit
JSON and no-cache headers and contains no HTML rendering.

## DVRIP section mapping

The initial allow-list is based on getters already verified against Balcon:

| UI section | DVRIP getter |
|---|---|
| `general` | `General` |
| `network` | `NetWork` |
| `camera` | `Camera` |
| `detection` | `Detect` |
| `encoding` | `Simplify.Encode` |
| `advanced_encoding` | `AVEnc` |
| `recording` | `Record` |
| `storage` | `Storage` |
| `alarms` | `Alarm` |
| `uart` | `Uart` |
| `video_ui` | `fVideo` |
| `capabilities` | `SystemFunction` |
| `system` | `SystemInfo` |
| `ability` | `Ability` |

Generic names that returned only DVRIP envelopes during discovery are excluded
until their value is understood. Factory-default queries are excluded because
Balcon did not provide reliable replies.

## Error handling

The API distinguishes:

- Invalid request or camera ID.
- Missing/invalid private configuration.
- Connection refused or timed out.
- Login rejected.
- Unsupported DVRIP branch.
- Malformed header or JSON.
- Oversized or incomplete response.
- Internal application error.

The SPA shows errors in context and retains its camera selection. During an
`all` request, each section reports success or failure independently. Retry is
explicit; the browser does not create an uncontrolled retry loop.

## Testing and verification

Automated PHP tests will use captured, sanitized binary/JSON fixtures and local
socket doubles where practical. They will cover:

- Header encoding and decoding.
- Session and sequence handling.
- Partial reads and writes.
- Malformed and oversized packets.
- Login success/failure parsing.
- Getter response parsing.
- Configuration validation and safe camera listing.
- API allow-list enforcement and error envelopes.
- Sanitization of private fields.

Static verification includes PHP syntax checks and `git diff --check`. Browser
verification covers initial selection, connect flow, section navigation,
loading states, tree expansion, refresh, and error presentation.

Live verification against Balcon remains read-only and uses only the approved
getter set. No JavaScript build command is needed because the SPA uses native
browser modules and CSS.

## Implementation sequence

1. Normalize the private Balcon configuration structure.
2. Add protocol fixtures and tests for the DVRIP transport.
3. Implement the minimal read-only PHP DVRIP client.
4. Implement config loading, service mapping, and the JSON dispatcher.
5. Build the native JavaScript SPA and CSS.
6. Run static and fixture-based tests.
7. Perform a narrow read-only live verification against Balcon.
8. Update the investigation documentation with verified implementation facts.

Writable controls are a separate future design. They require per-field types,
enum/range validation, current-state snapshots, risk classification, and an
explicit mutation workflow.
