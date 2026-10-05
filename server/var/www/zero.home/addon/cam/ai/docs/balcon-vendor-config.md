# Balcon vendor configuration inventory

Last updated: 2026-10-05 (Europe/Moscow)

## Method and safety

Balcon was accessed through Xiongmai/XMEye DVRIP (Sofia) on TCP 34567 with the
same login visible in the working Windows CMS. OpenIPC `python-dvr` was used
from a temporary checkout under `/tmp`.

Only getter operations were issued. Confirmed top-level families now include
`SystemFunction`, `SystemInfo`, `General`, `NetWork`, `Camera`, `Detect`,
`Simplify.Encode`, `AVEnc`, `Record`, `Storage`, `Uart`, `Alarm`, `fVideo`, and
`Ability`. No setter or operational command was sent. This inventory records
capabilities and schema names, not private configuration values.

## Confirmed capability map

### Alarm and communications

The firmware claims support for alarm configuration, blind detection, human
and pedestrian detection, video-loss detection, motion detection, network
interruption/alarm/IP conflict, storage alarms, RS-232, RS-485, and two-way
talk. The returned detection tree confirms motion, human, blind, loss, and
car-shape sections.

### Network services

The firmware explicitly reports support for:

- Alarm center/server
- DDNS
- DHCP and DNS
- Email/SMTP and mail testing
- FTP and FTP testing
- IP filtering
- NTP
- NAT/cloud
- PMS and PMS v2 push services
- RTSP
- UPnP
- Wi-Fi, mode switching, and signal levels
- ONVIF password checking and ONVIF port changes

Multicast exists in the returned tree, but the capability map reports it as
unsupported.

### Other functions

Reported functions include main/extra/snapshot streams, white-light and
double-light controls, app binding, Bluetooth, PTZ direction/tours/resume,
password recovery, volume, time zones, H.265X presentation, log writing, and
many hardware-specific controls. Firmware upgrade capabilities are present but
remain explicitly out of scope.

## Configuration branches

### Edit-form scope calculation

A fresh live inventory on 2026-10-05 returned all 14 allow-listed families and
116 top-level nodes. For UI planning these divide into:

- 9 absent/null placeholders;
- 27 explicitly read-only capability and system-information nodes;
- 12 internal, derived, hardware-limit, status, or definition-table nodes that
  should not be ordinary editors;
- 68 candidate editable configuration branches.

The 68 branches are backend save units, not 68 separate pages. A practical UI
can organize them into nine main pages with expandable panels:

| Page | Candidate save units |
|---|---:|
| General, time, and maintenance | 6 |
| Network and network services | 24 |
| Camera image and lighting | 6 |
| Detection | 6 |
| Encoding, color, OSD, and audio | 12 |
| Recording, snapshots, and storage | 5 |
| Alarms and event handling | 5 |
| PTZ and serial ports | 3 |
| Advanced/debug | 1 |
| **Total** | **68** |

This is an upper-bound implementation inventory, not proof that every branch
accepts writes. Each save unit still requires a controlled write/read-back
test, field typing, enum/range discovery, and risk classification. Repeated
structures such as server credentials, schedules, video streams, and event
handlers should use shared form components rather than duplicate code.

### `General`

- App-binding state
- Automatic maintenance, file retention, and scheduled reboot
- Daylight-saving state
- Machine identity and output behavior
- Date/time formats, language, PAL/NTSC, and work days
- Password recovery questions, security email/phone, and verification mode
- PTZ resume state: presets, tours, scans, patterns, and power-off positions

### `NetWork`

| Branch | Fields/categories |
|---|---|
| `AlarmServer` | enable, alarm/log flags, protocol, server address/name/port/user/password |
| `CheckNetState` | enable and reboot count |
| `DAS` | enable, device ID, server address/port/user/password |
| `DisableXmServerConn` | disable all, DSS, NAT, or RPS vendor connections |
| `IPAdaptive` | adaptive-IP toggle |
| `Multicast` | enable, address, port |
| `Nat` | enable, service address/port, DNS servers, XMEye MTU |
| `NetCommon` | IP/gateway/mask/hostname/MAC, HTTP/SSL/TCP/UDP ports, connections, transfer plan |
| `NetDDNS` | enable, provider key, hostname, address/port/user/password |
| `NetDHCP` | per-interface enablement |
| `NetDNS` | primary and secondary addresses |
| `NetEmail` | enable, SMTP endpoint/credentials, sender, recipients, subject, schedule, SSL mode |
| `NetFTP` | enable, endpoint/credentials, directory, maximum file length |
| `NetIPFilter` | enable, trusted list, banned list |
| `NetNTP` | enable, server/port, timezone, update period |
| `NetOrder` | network priority/order |
| `NetPPPoE` | enable, assigned address, endpoint/credentials |
| `OnlineUpgrade` | automatic checks/upgrades, version controls, server/port |
| `OnvifPwdCheckout` | enable |
| `PMS` | enable, box ID, server name, port, push interval |
| `RTSP` | client/server modes and endpoint credentials |
| `TranCommData` | enable, serial type, server type/address/port |
| `Upnp` | enable/state and HTTP/media/mobile ports |
| `Wifi` | enable, SSID, auth/encryption/key, channel, mode, IP/mask/gateway |
| `WifiStrengthLevel` | signal thresholds |

This branch covers essentially every item visible on the Windows CMS
`System -> NetService` page.

### `Camera`

- Defog; lens distortion; fisheye geometry/view/zoom
- Exposure sensitivity and limits; aperture; backlight compensation; shutter
- Gain; day/night color; noise filtering; IR-cut and infrared swap
- Anti-flicker; white balance; mirror and flip
- Automatic adjustment; WDR; corridor mode; stabilization; low-light mode;
  overexposure prevention; photosensitive control; image style
- White-light brightness, work mode, motion trigger, and schedule

Many fields are vendor enum integers or hexadecimal strings. They must not
become writable UI controls until their labels and safe ranges are established.

### `Detect`

Confirmed sections include blind, car-shape, human, video-loss, motion, and
tracking detection. Event handlers can route events to alarm output, buzzer,
FTP, logs, email, network messages, recordings, snapshots, PTZ links, tours,
on-screen information, and voice prompts. They include schedules, latch/debounce
timings, masks, regions, lines, sensitivity, and push-related controls.

### `Simplify.Encode`

Main and extra formats expose video/audio enablement, codec, resolution, frame
rate, bitrate and control mode, GOP, virtual GOP, and quality.

### Additional verified families

| Family | Confirmed contents |
|---|---|
| `AVEnc` | encoding tables, smart H.264/H.265 controls, video color and widgets, multi-lens data |
| `Record` | mode, channel mask, pre-recording, packet length, redundancy, and time sections |
| `Storage` | snapshots plus missing/failing/low-space storage alarms and storage position |
| `Uart` | generic serial, RS-485, PTZ configuration, presets, tours, and patterns |
| `Alarm` | alarm inputs/outputs, network abort/alarm, and IP-conflict events |
| `fVideo` | OSD text/placement, logo, GUI display settings, input/output volume |
| `Ability` | password-rule checking, telnet-debug capability, voice prompt types, and rule limits |

Read-only queries also return encoder capabilities, record/storage status, and
storage information. Generic names such as `Ptz`, `GUI`, `Channel`, `Video`,
`Audio`, and `Snap` returned protocol envelopes rather than a useful settings
schema and should not yet be treated as writable configuration families.

The firmware did not provide usable responses to DVRIP factory-default queries
for the tested major families. Consequently, a redacted snapshot of the
current state is the correct rollback/baseline source; the application must not
assume that a default-value endpoint exists.

## Legacy control static inspection

The camera's `/config.js` identifies `NewActive.exe`. On 2026-10-05 its vendor
URL redirected to `https://ocx.jftechws.com/ocx/NewActive.exe`; the downloaded
installer had SHA-256
`e7ad583d10d3606c10843d0ec9b4c015903998206bf53306b728826942f953f0`.
It was inspected as data and was never executed. Its payload includes
`ConfigModule.dll`, `NetSdk.dll`, `web.ocx`, and language resources.

The embedded English resources provide useful human labels for the raw DVRIP
schema. They confirm UI coverage for network services, RS-232/RS-485, PTZ,
recording, storage, alarms, camera/image controls, encoding, OSD, snapshots,
and general settings. They also contain warnings that some network/port changes
require a reboot or force a new login. These resources are valuable for naming
and risk classification, but they are generic XM software and must not be used
as proof that every displayed option is supported by Balcon.

## Firmware search and newer-image inspection

On 2026-10-05, the exact firmware identity was searched using Balcon's device
model `IPC_NT98566_IPG-N2-WQ2_S38` and catalog ID `000639L0`. A matching vendor
firmware archive exists for
`IPC_NT98566_IPG-N2-WQ2_S38.713g.Nat.dss.OnvifS`, built on 2022-07-20 and
published as a 2022-07-28 archive. This is newer than Balcon's currently
reported 2022-01-14 build. The downloaded archive had SHA-256:

`6b423057ffce2ea5f48bcaef648cc110e9b7e63b3e148ee432902f85d709ff6c`

The archive was inspected statically and was never uploaded to the camera. Its
`web-x.squashfs.img` still contains the legacy `NetSurveillance` interface,
references `ocx/NewActive.exe`, detects `WEB.WebCtrl.1` through ActiveX, and
prompts users to install IE ActiveX or a browser plugin. Therefore this exact
newer stock firmware does **not** provide evidence of an ActiveX-free modern
web configuration interface and is not worth flashing merely to replace the
browser UI.

OpenIPC lists Novatek NT98566 support as work in progress / experts only, with
manual bootloader installation and no ready solution for this board. It is not
a safe drop-in firmware option for Balcon at this time.

## Feasibility conclusion

A modern ALINA web replacement for the vendor console is technically feasible.
The server-side adapter can use DVRIP while the browser receives a sanitized,
typed model. ONVIF should remain a secondary source for standardized discovery
and cross-checking, not the primary configuration backend.

The first getter-only implementation now confirms this architecture in native
PHP. Balcon accepts PHP-generated DVRIP login and getter packets, and all 14
allow-listed configuration families returned successfully in the live
verification. An important protocol detail discovered during implementation is
that Balcon replies with header version 1 even though compatible requests are
sent with header version 0; the decoder accepts both response versions. Balcon
also formats payload session IDs with lowercase hexadecimal letters (for
example, `0x000000ad`). Session identity must therefore be compared numerically
rather than with case-sensitive text; the earlier comparison caused apparently
intermittent reconnect failures whenever the session ID contained `a`–`f`.

### Verified write operation

On 2026-10-05, write support was tested against Balcon with the narrow
`NetWork.NetEmail` configuration object. The existing object was read, its
global `Enable` field was explicitly set to `false`, and the complete object
was submitted with DVRIP configuration-set command 1040. The camera returned
success code `Ret = 100` and identified the response as
`NetWork.NetEmail`. An immediate DVRIP read-back exactly matched the submitted
object, and an independent read through the PHP application also returned
`NetEmail.Enable = false`. To prove that the camera persisted an actual value
change rather than merely accepting an idempotent write, the SMTP title was
changed from `BALCON` to `SMTP DISABLED`; both direct and PHP read-back returned
the new title.

This proves that Balcon accepts authenticated DVRIP configuration writes. SMTP
is globally disabled. Apart from the harmless SMTP title marker, no server,
account, recipient, schedule, alarm-routing, or unrelated network fields were
changed during this probe.

The web application now includes its first validated setter: a dedicated
Email / SMTP form. It performs a fresh read, merges only the fields owned by the
form, submits the complete `NetWork.NetEmail` object, and verifies an immediate
read-back. Hidden fields and an unchanged password are preserved; the password
itself is never returned to the browser. On 2026-10-05 the production endpoint
successfully completed an idempotent save of the current disabled SMTP values,
including exact read-back verification.

The next validated form batch covers `NetWork.NetFTP`, `NetWork.NetNTP`,
`NetWork.Upnp`, `NetWork.PMS`, and `NetWork.DAS`. Live read-only requests on
2026-10-05 confirmed that all five Balcon branches match their typed form
schemas. No write was performed during that verification. FTP, UPnP, PMS, and
DAS were disabled; NTP was enabled. As with SMTP, password fields are never
returned to the browser and a blank password means preserve the current value.

The second form batch covers `NetWork.Multicast`, `NetWork.NetPPPoE`, the server
portion of `NetWork.RTSP`, `NetWork.TranCommData`, and `NetWork.Nat`. Live
read-only verification confirmed all five structures. At verification time,
Multicast and the RTSP server were enabled; PPPoE, serial bridging, and vendor
cloud/NAT were disabled. No values were written. The UI warns that RTSP changes
can interrupt MotionEye and that PPPoE or vendor-cloud enablement can affect
connectivity or external communication.

The third batch adds `NetWork.NetDHCP`, `NetWork.Wifi`,
`NetWork.WifiStrengthLevel`, `NetWork.DisableXmServerConn`,
`NetWork.OnlineUpgrade`, and five provider-specific pages backed by the shared
`NetWork.NetDDNS` array. Live read-only verification matched all ten schemas.
At that time Wi-Fi and all five DDNS providers were disabled; DHCP was enabled
on `eth0`; online-upgrade service was enabled but its automatic check and
automatic important-upgrade flags were off. No camera setting was written.

The fourth batch adds `General.General`, `General.Location`,
`Camera.ClearFog`, `Camera.WhiteLight`, `fVideo.GUISet`, `fVideo.OsdLogo`,
`fVideo.Volume`, `fVideo.VolumeIn`, `Detect.DetectTrack`, and the basic scalar
controls from `Detect.HumanDetection`. Live read-only verification confirmed
all ten dotted DVRIP getters. Human-detection regions, rules, schedules, and
event actions are intentionally not editable in this batch and remain preserved
during a save. No camera setting was written.

Before exposing setters:

1. Save a complete redacted current snapshot (default queries are unreliable).
2. Enumerate additional documented DVRIP branches.
3. Derive enum labels and ranges from CMS or controlled comparisons.
4. Classify each field as read-only, reversible, connectivity-risking,
   restart-triggering, destructive, or unknown.
5. Build and test a getter-only application first.

## Existing software assessment

The closest ready-made application found is
`https://github.com/Moxnatiy/hass-xmeye`. It is a Home Assistant integration
with a browser panel that exposes the complete DVRIP configuration tree,
section by section, plus typed forms for selected commonly changed settings.
It also provides generic `xmeye.get_config` and `xmeye.set_config` services and
a dependency-free asynchronous Python DVRIP client. Its documented reference
device is an Xiongmai NVR on V4 firmware, not this V5 IPC, so Balcon compatibility
must be tested rather than assumed. Requiring Home Assistant solely for camera
configuration may also be excessive when MotionEye already provides daily
monitoring.

Other candidates do not meet the full-configuration requirement:

- `sofia-netsurv/sofia-client` targets exactly this use case but declares
  itself work-in-progress/not ready for release and provides no release.
- OpenIPC `python-dvr` is the strongest low-level control library and proved
  compatible with Balcon, but it is not a complete modern configuration UI.
- BelEye concentrates on live view, archive playback, and export.
- Cayenue concentrates on ONVIF/NVR functions and cannot expose the full DVRIP
  vendor tree.

Recommendation: first perform a disposable compatibility trial of
`hass-xmeye` if a Home Assistant instance is acceptable. For the durable ALINA
solution, reuse or adapt an existing DVRIP client—preferably the modern async
client from `hass-xmeye`, cross-checked against OpenIPC `python-dvr`—and build a
small dedicated configuration web application. Do not reimplement the binary
protocol from scratch, and do not duplicate MotionEye's viewer/NVR functions.
