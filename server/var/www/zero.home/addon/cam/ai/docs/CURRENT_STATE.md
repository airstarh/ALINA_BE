# Camera application current state

Last updated: 2026-10-06 (Europe/Moscow)

This is the hand-off document for continuing work on the camera application.
It describes the implementation and verified Balcon behaviour without storing
credentials. Camera credentials belong only in the application's local
`config.php` and the out-of-Git credential notes requested by the owner.

## Goal and scope

The application at `/addon/cam/` is a native PHP and browser-JavaScript
replacement for the legacy Xiongmai ActiveX configuration console. MotionEye
continues to handle everyday video use. This project focuses on exposing the
camera's extensive vendor configuration through DVRIP.

The current camera is **Balcon**. Its configured management endpoint is the
wired interface on DVRIP TCP port 34567. The application intentionally obtains
the host and credentials only from `addon/cam/config.php`; credentials are not
entered in the browser.

## Architecture

- `index.php` renders the shell and camera selector.
- `stuff/js/app.js` controls connection, navigation, forms, and reboot UI.
- `stuff/js/api.js` is the browser HTTP/JSON client.
- `stuff/php/api.php` validates HTTP actions, loads config, serializes requests
  per camera with a directory lock, logs in, dispatches, and closes the socket.
- `CameraService.php` exposes application operations and the read-only section
  catalogue.
- `NetworkServiceEditor.php` and `ExtendedEditorDefinitions.php` contain the
  allow-listed typed editor schemas and the merge/write/verify implementation.
- `DvripClient.php` creates logical DVRIP requests.
- `DvripPacketCodec.php` serializes/deserializes the binary DVRIP header and
  JSON payload.
- `StreamDvripTransport.php` uses PHP `stream_socket_client()`, `fwrite()`, and
  `fread()` directly. There is no Composer DVRIP dependency and no permanent
  worker or infinite loop.

The browser communicates with PHP over ordinary HTTPS/AJAX. PHP communicates
with the camera using DVRIP over a separate raw TCP socket. JavaScript cannot
directly open this arbitrary TCP protocol from a normal browser.

## Implemented functionality

- Camera catalogue with Balcon selected by default.
- Connection/status check and read-only explorer.
- Fourteen read-only families: General, NetWork, Camera, Detect,
  Simplify.Encode, AVEnc, Record, Storage, Alarm, Uart, fVideo,
  SystemFunction, SystemInfo, and Ability.
- A dedicated Email / SMTP editor.
- 68 allow-listed schema-driven editor pages covering network services,
  general settings, image controls, detection, streams, recording/storage,
  alarms, audio/OSD, and UART/PTZ configuration.
- Wi-Fi AP search using the camera itself, with a `Use` action to populate the
  form.
- Wi-Fi's `eth1` DHCP switch mirrored into the Wi-Fi form while remaining on
  the dedicated DHCP page. Static IP controls stay visible but disabled while
  DHCP is enabled.
- Human-readable Wi-Fi options with Balcon-specific wire translation for the
  live-tested q001 WPA2/AES network.
- All editable password/key fields intentionally use visible text inputs by
  explicit homelab design. The raw explorer still redacts secrets.
- Globally distinct light-gray styling for disabled controls.
- Header reboot button with confirmation and a POST-only backend action.
- Automatic per-camera editor discovery: only getters are used, required
  fields are validated, and irrelevant/unsupported forms are omitted.

## Save semantics

No form submits an arbitrary branch name. The server chooses an allow-listed
branch from the editor ID. A normal save performs:

1. read the latest complete branch from the camera;
2. validate browser-owned fields;
3. merge those fields into the fresh object, preserving everything else;
4. send the complete named branch with DVRIP message 1040;
5. read it back and verify the result before reporting success.

SMTP follows the same model with dedicated validation. Wi-Fi also reads the
`NetWork.NetDHCP` array and, when necessary, writes the full updated array to
change only `eth1.Enable`.

## Balcon protocol discoveries

- DVRIP login works on TCP 34567 using `LoginType = DVRIP-Web` and the Sofia
  password hash.
- Requests use protocol header version 0, while Balcon may reply with version
  1. The decoder accepts both.
- Session IDs may contain lowercase hexadecimal digits. They are compared
  numerically, fixing the earlier intermittent reconnect failure.
- Configuration get is message 1042; configuration set is 1040.
- General information get is message 1020; system capabilities use 1360.
- Wi-Fi AP search is message 1020 with `Name = WifiAP`.
- Reboot is message 1450 with `OPMachine.Action = Reboot`.
- Balcon may cancel reboot if the client disconnects immediately. The backend
  holds the acknowledged connection open for 12 seconds.
- Wi-Fi IPv4 values use a little-endian hexadecimal representation.
- Enabled Wi-Fi may return control bytes or misleading legacy strings for
  authentication/encryption. For the verified q001 configuration the UI
  displays WPA2 Personal/AES, while this Balcon setter requires the legacy wire
  values `WPAPSK`/`TKIP`; read and write translations are intentional.
- The Wi-Fi password format is firmware-derived and locked in the UI.

## Verified mutations and operational findings

- SMTP write support was proven with exact read-back. SMTP was disabled and
  its title changed to `SMTP DISABLED`; unrelated SMTP fields were preserved.
- Wi-Fi settings were experimentally written and read back while establishing
  the q001 enum translations.
- Wi-Fi was disabled. Its former wireless address remained reachable until the
  camera rebooted; after reboot it went offline while the configured wired
  address remained reachable.
- The reboot backend was live-tested successfully. A later non-working browser
  button was traced to mismatched JavaScript module cache versions, not DVRIP.
  The module versions were bumped and a browser API regression test was added.
- Door was added as a second configured camera. Its standard reboot command was
  live-verified through temporary network disappearance and recovery.
- Door exposes 56 applicable schema-driven editors; Balcon exposes all 67.
  Door's dummy Wi-Fi branch is suppressed because it lacks the `NetWifi`
  capability. Discovery reads each top-level family once and takes about two
  seconds on either camera.

## Latest verification

After the reboot-button cache fix:

- 75 PHP tests passed with zero failures;
- JavaScript UI tests passed;
- JavaScript syntax checks passed;
- `git diff --check` passed;
- the live server served `app.js?v=20261006-4`, which imports
  `api.js?v=20261006-3` containing `CameraApi.reboot()`.

No JavaScript SPA build is required for this application. Do not run one
without the owner's immediate explicit permission. No commit was created.

## Important constraints for continued work

- Preserve unrelated working-tree changes and never commit unless explicitly
  requested.
- Treat the source code and live read-back as authoritative; legacy CMS labels
  are supporting evidence, not proof of accepted values.
- Never invent enum wire values. Establish mappings through source evidence,
  packet capture, or controlled write/read-back.
- Avoid firmware upgrade, factory reset, account changes, or broad network
  changes unless explicitly requested.
- Keep camera credentials out of these tracked AI notes.

See [DVRIP_CALL_MAP.md](DVRIP_CALL_MAP.md) for the exact browser-to-wire map.
