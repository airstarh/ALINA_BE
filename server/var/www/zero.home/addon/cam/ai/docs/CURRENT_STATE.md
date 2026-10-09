# Camera application current state

Last updated: 2026-10-09 (Europe/Moscow)

This is the hand-off document for continuing work on the camera application.
It describes the implementation and verified Balcon behaviour without storing
credentials. Camera credentials belong only in the application's local
`config.php` and the out-of-Git credential notes requested by the owner.

## Goal and scope

The application at `/addon/cam/` is a native PHP and browser-JavaScript
replacement for the legacy Xiongmai ActiveX configuration console. MotionEye
continues to handle everyday video use. This project focuses on exposing the
camera's extensive vendor configuration through DVRIP.

The configured cameras include **Balcon** and **Door**. Their management
endpoints use DVRIP TCP port 34567. The application intentionally obtains
the host and credentials only from `addon/cam/config.php`; credentials are not
entered in the browser.

## Architecture

- `index.php` renders the shell and camera selector.
- `stuff/js/app.js` controls connection, navigation, forms, and reboot UI.
- `stuff/js/rule-form.js` overlays the editable first human-detection rule on a
  current JPEG from `stuff/php/snapshot.php`. `rule-geometry.js` maps pointer
  positions to the camera's normalized 0–8192 grid.
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

- Main/extra stream editors use camera-reported codec/resolution options and
  app-provided dropdown choices for FPS, bitrate, CBR/VBR, quality code and
  I-frame interval (GOP). Current values are retained; bitrate presets are
  filtered by the camera's reported MaxBitrate. Help text distinguishes presets
  from advertised capabilities. This UI change was not tested, at user request.
- **ACCESS → All points** is first in the menu. It discovers per-camera DVRIP,
  web/JPEG, ONVIF services and RTSP addresses, distinguishes verified listeners
  from configured/unverified ports. It manages actual camera accounts used by
  DVRIP and RTSP through one native USERS table, plus collapsible GROUPS and
  SERVICES tables. Visible new-password inputs, explicit Set empty, Multilogin,
  per-user permissions and supported Memo are editable. One bottom Save changes
  button writes only changed rows; partial failures retain pending edits. Only inbound listening services are
  shown; URLs appear directly under service headings. Balcon's RTSP password
  enforcement is defective and this is explicitly shown in its service notes.
  See [access-points.md](access-points.md) for live findings, limits and optional
  per-service config overrides, provenance limits and verified port rollback.
- Follow-up cross-check: Balcon ONVIF-created users also appear in the native
  DVRIP/CMS user list, with individual permissions. The previous separate-store
  conclusion was incorrect; see access-points.md for verified role mappings.
- Camera catalogue with Balcon selected by default.
- Connection/status check and read-only explorer.
- Fourteen read-only families: General, NetWork, Camera, Detect,
  Simplify.Encode, AVEnc, Record, Storage, Alarm, Uart, fVideo,
  SystemFunction, SystemInfo, and Ability.
- A dedicated Email / SMTP editor.
- A **Test email** action which checks the camera's `SupportMailTest` capability,
  freshly reads its saved `NetWork.NetEmail` branch, and issues DVRIP
  `OPMailTest` (message 1636) without saving or changing camera configuration.
- A **Test FTP connection** action on the FTP editor. It checks
  `SupportFTPTest`, reads saved `NetWork.NetFTP`, and issues DVRIP `OPFTPTest`
  (message 2008). Unsaved form edits are not sent. Balcon's live test on
  2026-10-07 returned DVRIP code 109 (failure). Packet capture on `bbb`
  showed successful FTP login but `550` when the camera attempted `MKD Test`
  at the FTP login root. With `local_root=/`, that root is the host's `/`,
  which the FTP user cannot write. The app now shows the test result inline
  beside the button as well as in the page's top status line. No camera or
  server configuration was changed during diagnosis.
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
- Main/extra stream Codec controls use selects populated from the camera's
  per-stream `EncodeCapability` compression mask. Door's Main stream currently
  reports mask `0x00000180`, which advertises H.264 and H.265; its current codec
  is H.264. The UI labels these clearly while submitting the camera's exact
  existing wire strings (`H.264` / `H.265`).
- Main/extra stream Resolution controls are camera-specific selects populated
  from the matching `EncodeCapability.EncodeInfo[].ResolutionMask`. Labels are
  human-readable while option values remain the Xiongmai DVRIP strings (for
  example `1080P` or `D1`). The live masks decode to Balcon Main `1080P`;
  Balcon Extra `D1`, `HD1`, `CIF`, `QVGA`; Door Main `720P`, `1080P`, `5M`,
  `4M`; and Door Extra `D1`, `HD1`, `CIF`, `QVGA`. Stream saves re-check the
  capability and reject values outside the reported mask, while allowing the
  exact current value if firmware omits it from the mask.
- A Wired network editor combines `NetWork.NetCommon` with DHCP on `eth0` and
  exposes static IP, subnet, gateway, and hostname. MAC is read-only.

## Human Detection feature status

The Human detection form edits the allow-listed top-level settings plus the
first rule's enabled state and verified type (`0 = Tripwire`, `1 = Intrusion
area`) in `Detect.HumanDetection`. It also reads App Push from
`Detect.MotionDetect.[0].EventHandler.MessageEnable`, the exact field changed
by a captured native-CMS App Push toggle. Door's captured Send Email toggle
changes `Detect.MotionDetect.[0].EventHandler.MailEnable` and uses the
separately configured SMTP server. The FTP and Write log actions now use the
same shared event-handler branch: `FTPEnable` is offered only when the camera
also advertises `NetServerFunction.NetFTP`, while `LogEnable` is offered when
the live field is Boolean. Both are available on Balcon and Door; neither
requires a per-camera list in `config.php`. Their destination/server settings
remain on the separate FTP page. The `Include event snapshot` control edits
the first-channel `SnapShotMask` bit and `SnapEnable` in the same shared action
branch; SMTP configuration and Send Email remain separate. Its wire mapping is
supported by the local SDK header, earlier CMS captures, and current read-back,
but a one-control native capture and resulting email attachment test are still
pending. Balcon's captured Alarm Sound toggle
changes `Detect.MotionDetect.[0].EventHandler.VoiceEnable`; this control is
offered only when `OtherFunction.SupportCloseVoiceTip` is true. That flag is a
conservative observed distinction between Balcon and physically silent Door,
not a universal speaker-detection guarantee. Balcon's sound selector reads its
allowed numeric codes from `Ability.VoiceTipType.VoiceTip[].VoiceEnum` and the
current code from `Detect.MotionDetect.[0].EventHandler.VoiceType`. It never
offers an unadvertised new code. `config.php` may override speaker support,
provide code-to-label names, or provide a fallback code-to-label list when
the ability query is unavailable. Only Balcon code `524 = Bell Alarm` is
currently confirmed by the owner's CMS screenshot; other codes are labelled
unverified until their correspondence is captured. Door's selector remains
hidden despite its generic VoiceTip ability list. Each changed branch is freshly
read, written in full, and verified by read-back. The collapsed Detection rule
/ area section edits Tripwire endpoints or one four-sided Intrusion area. The
inactive shape, direction flags, and other rule slots are preserved. Geometry
edits carry their original points so a concurrent camera change is rejected
before writing. The Period section edits the camera's seven-day,
six-slot `EventHandler.TimeSection` on the same page. It submits only changed
slots, rejects stale slots, and preserves every other slot during the
fresh-read merge. PTZ activation now appears as a collapsed subsection only
when the camera advertises direction control and a usable preset/tour capability.
It also offers the documented Pattern wire action, clearly noting that
Balcon's pattern playback has not been verified. The saved-slot number input
appears only for Preset, Tour, or Pattern, with action-specific guidance; it
has no invented upper bound. Existing Pattern/0 is preserved when other alert
settings are saved.
Balcon's motor was verified with short four-direction ONVIF moves and explicit
stops; Door has no PTZ capability flags, is physically static, and its PTZ
controls stay hidden. The editor owns only shared alert `PtzLink[0]` and sets
`PtzEnable` when an action is selected, preserving all other channels. A live
A live PTZ-link write test found `Preset/1`, `Tour/1`, and `Pattern/1` accepted and
fresh-read verified on Balcon with Human Detection temporarily disabled;
the original detection and `None/0` link settings were restored and verified.
The owner subsequently saved Presets 1–5; direct recall
confirmed distinct balcony-facing and city-facing views. Two timed alert-link
tests observed no transition or new Balcon FTP snapshot. The owner later
confirmed they did not walk into view during either window, so these tests
did not exercise event-to-PTZ linkage. Direct preset recall works; an
alert-triggered recall remains unverified.
The original alert settings and city-facing view were restored. Generic ONVIF
preset enumeration is identical on both cameras and is not considered evidence
of a saved preset. No probe endpoint remains in the app. See
[`human-detection-discovery.md`](human-detection-discovery.md) for exact
observations.

On 2026-10-07 the owner tested the polygon and Tripwire editors in the browser:
mouse manipulation worked, both saved shapes appeared exactly the same in the
native CMS/ActiveX editor, and the overlay stayed aligned after resizing the
browser window. This is user-reported end-to-end confirmation. Rule coordinates
are normalized 0–8192 rather than tied to a particular camera image resolution;
the overlay maps pointer positions using the image's current rendered bounds.
The owner's later resolution-change check found the rule overlay correct.

On 2026-10-07 a
Balcon walk test activated its light and sound but produced no email or FTP
JPEG, despite enabled mail/FTP/snapshot action flags and passing standalone
connection tests. The earlier `ManualSnap` experiment therefore did not solve
the problem. Balcon is now set to `Storage.Snapshot[0].SnapMode=ConfigSnap`.
The owner's 19:29 Human DetectStart event produced an email with JPEG
attachment, and two matching Balcon JPEGs appeared in the FTP destination.
This verifies both delivery paths for that event; see
[`storage-options.md`](storage-options.md) for evidence and limits.

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

The SMTP test is a distinct, POST-only operation. It tests the settings
currently saved on the camera, not unsaved browser edits. A positive DVRIP
response means the camera accepted the test request; it does not prove the
message reached the recipient's inbox.

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
- Door's SMTP test was live-tested through `OPMailTest` after confirming the
  saved settings were enabled and a recipient was configured. The camera
  accepted the request. No SMTP settings were written or changed by the test.
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
- Door exposes 57 applicable schema-driven editors; Balcon exposes all 68.
  Door's dummy Wi-Fi branch is suppressed because it lacks the `NetWifi`
  capability. Discovery reads each top-level family once and takes about two
  seconds on either camera.

## Latest verification

For the 2026-10-07 rule editor implementation:

- 90 PHP tests and 26 JavaScript tests passed with zero failures after the
  event-snapshot switch was added;
- PHP and JavaScript syntax checks and `git diff --check` passed;
- read-only live JPEG and human-detection model requests worked for both
  Balcon and Door;
- the owner subsequently confirmed saved geometry in native CMS and responsive
  browser resizing.

Earlier, after the reboot-button cache fix:

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
