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
sent with header version 0; the decoder accepts both response versions.

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
