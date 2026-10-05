# Balcon camera: safe discovery

Last updated: 2026-10-05 (Europe/Moscow)

## Scope

Read-only investigation of the camera named **Balcon**. No camera settings were
changed. No video or audio was recorded. No firmware, restart, reset, PTZ, or
network-management action was invoked.

The credential source is `_GITOUT/addon/cam/creds.md`. Secrets are intentionally
omitted here.

## Endpoint

- Address: `192.168.1.121`
- Existing RTSP endpoint: port `554`, path `/stream0`
- Existing ONVIF device service: port `8899`, path `/onvif/device_service`
- Latency during the local probe: below 1 ms

## Confirmed TCP services

A narrow TCP connect/version probe checked only ports commonly used by these
cameras: `80,443,554,8000,8080,8899,34567,37777`.

| Port | State | Confirmed behavior |
|---:|---|---|
| 80 | open | Legacy `NETSurveillance WEB` HTTP interface |
| 443 | closed | No HTTPS |
| 554 | open | `H264DVR rtspd 1.0`; RTSP methods include OPTIONS, DESCRIBE, SETUP, PLAY, PAUSE, TEARDOWN, GET_PARAMETER, and SET_PARAMETER |
| 8000 | closed | — |
| 8080 | closed | — |
| 8899 | open | ONVIF SOAP service implemented with gSOAP 2.7 |
| 34567 | open | XM NETIP/DVRIP (Sofia); read-only login and configuration getters confirmed |
| 37777 | closed | — |

Port 80 responds over plaintext HTTP and identifies the page as
`NETSurveillance WEB`. Port 8899 also uses plaintext HTTP. The camera advertises
no TLS support through ONVIF, so camera credentials and control traffic should
be considered observable by other devices on the same network.

## Device identity

An unauthenticated, read-only ONVIF `GetDeviceInformation` call returned:

- Manufacturer: `H264`
- Model: `IPC_NT98566_IPG-N2-WQ2_S38`
- Firmware: `V5.00.R02.000639L0.10010.140017..ONVIF 16.12`
- Hardware ID: `00001`
- ONVIF version claimed by the firmware: 16.12

The serial number is deliberately omitted from this tracked note.

## ONVIF behavior

The following read-only ONVIF requests succeeded without authentication:

- `GetSystemDateAndTime`
- `GetCapabilities` with category `All`
- `GetDeviceInformation`
- Media `GetProfiles`
- Media `GetStreamUri` for profiles `000` and `001`
- Imaging `GetImagingSettings` and `GetOptions` for source `000`

At probe time the camera reported manual clock configuration, local timezone
UTC+3 (`EAT-3` in its POSIX-style timezone notation), daylight saving enabled,
and a correct local date/time. Whether the daylight-saving flag has a practical
effect should be checked before relying on timestamps.

Advertised ONVIF service endpoints:

- Device: `/onvif/device_service`
- Media: `/onvif/Media`
- Imaging: `/onvif/imaging`
- PTZ: `/onvif/PTZ`
- Events: `/onvif/Events`
- Analytics: `/onvif/analytics_service`
- Vendor extension: `/onvif/telecom_service`

Advertised features include RTP over TCP, RTSP interleaved over TCP, pull-point
events, imaging control, PTZ, motion detection, tamper detection, and a maximum
of two media profiles. These are capability claims; only device information,
time, capabilities, and profile reading have been exercised so far.

The camera reports no TLS 1.0/1.1/1.2 support and no IPv6, dynamic DNS, zero
configuration, ONVIF system backup, or ONVIF system logging. It advertises an
HTTP firmware-upgrade extension. Firmware operations are explicitly out of
scope and must not be tested.

## ONVIF media profiles

Both profiles are fixed and share video source token `000`, audio source token
`000`, analytics configuration `000`, and PTZ configuration `000`.

| Profile | Video reported by ONVIF | Audio | Notable values |
|---|---|---|---|
| `000` | H.264 High, 1920×1080 | G.711, 64 kbit/s, 8 kHz | quality 3; frame-rate limit 20; bitrate limit 1072 kbit/s; GOP 12 |
| `001` | H.264 High, 704×576 | G.711, 64 kbit/s, 8 kHz | quality 3; frame-rate limit 10; bitrate limit 743 kbit/s; GOP 1 |

The analytics profile reports a 22×18 motion-cell grid, motion sensitivity 66,
tamper sensitivity 66, and motion on/off delays of 1000 ms. These values were
only read, not changed.

The PTZ profile exposes generic pan, tilt, and zoom ranges. This does not prove
that the installed camera has physical PTZ hardware; no movement command was
sent.

### Advertised encoder option ranges

The option query succeeded without authentication for both encoder tokens.
Both advertise quality 1–6, frame rate 1–25 fps, GOP length 1–12, encoding
interval fixed at 1, bitrate 32–8192 kbit/s, and H.264 Baseline/Main/High.
Profile `000` advertises only 1920×1080; profile `001` advertises only 704×576.

These ranges repeat the H.264 claim that conflicts with the HEVC stream actually
observed. They should be treated as unreliable until a reversible configuration
test is separately designed and approved.

## Direct RTSP observation

`ffprobe` connected to the configured `/stream0` URL over RTSP/TCP and read only
stream metadata; it did not save media.

- Video: HEVC/H.265, 1920×1080
- Reported video rates: `r_frame_rate=12/1`, `avg_frame_rate=25/1`
- Audio: G.711 A-law (`pcm_alaw`)

Important discrepancy: ONVIF profile `000` reports H.264, while the configured
vendor RTSP `/stream0` endpoint actually delivered HEVC/H.265. Possible causes
include stale or inaccurate ONVIF metadata, a vendor stream path that does not
map directly to the ONVIF profile, or an implementation bug. A standards-based
client must obtain each profile's ONVIF stream URI and probe it instead of
assuming `/stream0` represents profile `000`.

The two rate fields also disagree (12 versus 25 fps). A short packet-timestamp
sample would be needed to establish the effective frame rate; metadata alone is
not conclusive.

ONVIF returned vendor-style RTSP paths with `stream=0` for profile `000` and
`stream=1` for profile `001`. It placed `admin` and an empty password directly
in both URIs. A metadata-only connection to the returned `stream=0` URI worked
without a password and delivered the same HEVC 1920×1080 plus G.711 A-law
streams described above. The returned `stream=1` URI failed at RTSP DESCRIBE
with vendor status `451ERROR`; therefore the second profile is advertised but
not currently usable through its ONVIF-provided URI.

This confirms that the main video stream is accessible to any local client
without a password. The complete URI is omitted here to avoid turning the note
into a copy-and-paste unauthenticated access recipe.

## Current imaging settings

Read-only ONVIF imaging calls for source token `000` succeeded without
authentication. Current values include:

| Setting | Current value | Advertised option range |
|---|---:|---:|
| Brightness | 50 | 0–100 |
| Color saturation | 50 | 0–100 |
| Contrast | 50 | 0–100 |
| Sharpness | 8 | 0–15 |
| Backlight compensation | off | off only |
| Exposure | auto, low-noise priority | options not advertised |
| IR-cut filter | auto | options not advertised |
| Wide dynamic range | on, level 50 | options not advertised |
| White balance | auto | options not advertised |
| Focus | auto; default speed 5; limits 0–100 | options not advertised |

Several exposure fields all report the placeholder-like value `50`. They
should not be assumed to represent physical exposure units. More importantly,
the camera advertises writable ranges only for brightness, saturation,
contrast, and sharpness. A future UI should disable controls for settings not
confirmed by `GetOptions`, even when `GetImagingSettings` returns a value.

## Current network configuration

Read-only ONVIF network queries report:

- Interface token/name: `eth0`, enabled, MTU 1500
- IPv4: enabled, DHCP enabled, current lease `192.168.1.121/24`
- Link: reported as 10 Mbit/s full duplex with auto-negotiation disabled
- Enabled standard protocols: HTTP on 80 and RTSP on 554

The hardware address is omitted from this note. ONVIF does not list its own SOAP
listener on 8899 in `GetNetworkProtocols`, despite that service being active.
Because the address comes from DHCP, an address reservation should be verified
at the DHCP server before software assumes `192.168.1.121` is permanent. The
reported 10 Mbit/s fixed link may be genuine or another firmware reporting
quirk; no throughput test was performed.

## Security observations

- Device identity, capabilities, analytics layout, and media configuration are
  readable without authentication on the local network.
- The main RTSP video/audio stream is confirmed accessible with the empty
  password returned by ONVIF.
- The camera leaks an administrator-style RTSP URI from unauthenticated
  `GetStreamUri` calls.
- Management and media services are plaintext; there is no HTTPS listener.
- Port 34567 grants vendor-level configuration access and expands the attack
  surface even if ONVIF becomes the preferred management method. Balcon's
  DVRIP account accepted an empty password during the read-only probe.
- The camera should remain isolated from untrusted clients and should not be
  directly exposed to the Internet.
- A future ALINA application must keep credentials server-side. Browser code
  must never contain or receive the camera password unless there is no safer
  architecture.

## Cayenue 1.0.14 experiment

Cayenue 1.0.14 was installed from its Flatpak distribution on Kubuntu and
successfully connected to Balcon. It displayed the live 1920×1080 picture,
confirming that it can consume this camera's working HEVC stream despite the
incorrect H.264 metadata reported by ONVIF.

Cayenue is an ONVIF-oriented camera manager, not a replacement for every page
of the vendor CMS. Its documented camera controls concentrate on standard
media, imaging, basic networking, events, PTZ, streaming, recording, and
Cayenue's own analytics/proxy functions.

The Windows CMS `System -> NetService` page exposes IP Filter, DDNS, Email/SMTP,
NTP, UPnP, FTP, Wi-Fi, AlarmServer, RTSP, Cloud, App Push, and serial
pass-through. These are mostly vendor-specific configuration areas rather than
portable ONVIF services. Cayenue is therefore not expected to expose SMTP,
FTP, DDNS, cloud/P2P, app-push, alarm-server, or serial pass-through settings.
Some overlapping standard functions—basic IP configuration, DNS/NTP, RTSP,
users, events, and possibly Wi-Fi on devices that implement the relevant ONVIF
operations—may be available, but Balcon's incomplete ONVIF implementation and
Cayenue's UI determine the actual subset.

The successful Cayenue connection proves that Linux-native viewing and a useful
subset of management are possible. Access to the full vendor configuration
surface will require either continued use of the proprietary CMS or careful
reverse engineering/use of the camera's vendor protocol, likely the service on
TCP 34567. That vendor investigation must begin read-only and must not assume
that an undocumented getter or setter is safe.

## Vendor protocol and legacy web interface

The exact model/firmware fingerprint and a successful read-only login on port
34567 confirm that Balcon is a Xiongmai/XMEye-family device using DVRIP, also
called the Sofia protocol. The protocol uses a 20-byte binary header followed
by JSON and provides generic configuration get/set messages. OpenIPC's
`python-dvr` client describes itself as a library for devices using the
NETSurveillance ActiveX/XMEye SDK and proved compatible with this camera's
configuration getters.

Relevant references:

- `https://github.com/OpenIPC/python-dvr`
- `https://github.com/Moxnatiy/hass-xmeye/blob/main/docs/protocol.md`
- `https://github.com/KostasEreksonas/DVRIP_analysis`
- `https://www.xiongmaitech.com/en/index.php/service/problem_info/139/34`

The camera's HTTP root loads `m.jsp` and `config.js`, then instantiates ActiveX
class `WEB.WebCtrl.1` (`CLSID: FD3BEB0C-AB43-4253-9146-C371D48FBE0D`). The
Device Configuration menu calls only `ocx.ShowDeviceConfig()`. Consequently,
the many configuration forms are implemented inside the native ActiveX control,
not as HTML forms or discoverable HTTP API routes. The page points to
`NewActive.exe` at `xmsecu.com` and also references `web.cab`; on this camera a
request for `web.cab` returned the generic HTML page rather than a CAB archive.
No downloaded component was installed or executed.

DVRIP configuration is a nested JSON tree addressed by dotted names. Known
root/branch getters include `SystemFunction`, `SystemInfo`, `General`,
`NetWork`, `Camera`, `Detect`, and `Simplify.Encode`. The `SystemFunction`
response is effectively a capability map and commonly contains flags for
email, FTP, DDNS, DHCP, DNS, NTP, NAT/cloud, UPnP, Wi-Fi, alarm-center, serial,
motion detection, image processing, and many hardware-specific functions.
Reading a root such as `NetWork` or `Camera` can return an extensive subtree.

This means there is no single HTTP route that enumerates the legacy console,
but there is a practical equivalent: read the DVRIP capability map, then read
each supported configuration branch. Unknown or unsupported names return a
protocol error rather than requiring a setter.

A temporary copy of OpenIPC `python-dvr` was placed under `/tmp` for the probe.
No project dependency was installed. A screenshot of the working Windows CMS
confirmed that XM (NETIP) uses port 34567, username `admin`, and an empty
password. That exact DVRIP login succeeded. Because it succeeded, the proposed
`admin/admin` fallback was not attempted.

Authenticated getters returned `SystemFunction`, `SystemInfo`, `General`,
`NetWork`, `Camera`, `Detect`, and `Simplify.Encode`. Only capability booleans
and field names/types were printed; values that might contain passwords,
addresses, tokens, or email accounts were suppressed. The sanitized schema is
recorded in `balcon-vendor-config.md`.

No setter, reboot, upgrade, account mutation, PTZ, alarm subscription, or media
command was sent. The feasibility blocker is resolved: this camera's private
configuration is readable from Linux through DVRIP.

Security note: the camera accepts its administrator account with an empty
password on the plaintext DVRIP management service. Any client able to reach
TCP 34567 should be treated as capable of full administrative control. Network
isolation is urgent even though the camera is not exposed directly to the
Internet.

## Next safe checks

1. Save a machine-readable, fully redacted DVRIP current-state snapshot and
   derive typed enums/ranges from the legacy resources and capability replies.
2. Query event properties and PTZ node capabilities without creating a lasting
   subscription or moving the camera.
3. Investigate why profile `001` returns `451ERROR` and why both ONVIF profiles
   claim H.264 while the working main stream is HEVC.
4. Design a getter-only ALINA prototype before any setter experiment.
5. Plan camera isolation or access controls because changing the documented
   password alone may not protect the currently exposed main stream.

## Commands used

The investigation used narrow `nmap` TCP connect probes, unauthenticated ONVIF
SOAP POST requests with `curl`, a metadata-only `ffprobe` connection, read-only
DVRIP getters through a temporary OpenIPC `python-dvr` checkout, and static
string/resource inspection of the legacy installer. Exact credential-bearing
URLs are intentionally not reproduced. No downloaded Windows component was
executed and no camera mutation was requested.
