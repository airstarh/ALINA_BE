# DVRIP call map

Last updated: 2026-10-07 (Europe/Moscow)

This map follows the requested format:

`JavaScript call -> PHP method/function -> true raw DVRIP command`

“Raw command” below means the DVRIP message ID plus its decoded JSON payload.
`DvripPacketCodec::encode()` wraps that JSON in the binary DVRIP header. The
runtime session and sequence values are shown symbolically because the camera
assigns them per connection.

## Transport common to all camera calls

Every camera-facing HTTP request creates a short-lived service and first sends:

`CameraApi method -> createProductionApi() -> DvripClient::login()`

```text
DVRIP request message: 1000
Payload:
{
  "EncryptType": "MD5",
  "LoginType": "DVRIP-Web",
  "PassWord": "<8-character Sofia hash>",
  "UserName": "<configured username>"
}
Expected response message: 1001
```

PHP then sends the operation below on that authenticated TCP connection. The
request payload is terminated/encoded by `DvripPacketCodec`; it is not HTTP.
For every normal command, Balcon replies on `request message ID + 1`.

## Catalogue and local metadata

| JavaScript call | PHP method/function | Raw DVRIP command |
|---|---|---|
| `CameraApi.listCameras()` | `Api::handle('cameras')` -> `Config::publicCameras()` | **None.** Reads local `config.php`. |
| `CameraApi.sections(camera)` | `Api::handle('sections')` -> `CameraService::sectionList()` | **None.** Static PHP catalogue; current API returns before constructing a camera service. |
| `CameraApi.editors(camera)` | `Api::handle('editors')` -> `CameraService::availableEditors()` -> `NetworkServiceEditor::available()` | Read-only capability/system-info and configuration getters to filter the PHP allow-list for this device; exact getter commands depend on available branches. |

## Status and read-only configuration

| JavaScript call | PHP method/function | Raw DVRIP command |
|---|---|---|
| `CameraApi.status(camera)` | `Api::handle()` -> `CameraService::status()` -> `DvripClient::getSystemInfo()` | Message **1020**, payload `{"Name":"SystemInfo","SessionID":"0x<session>"}`. |
| `CameraApi.config(camera, section)` for `system` | `CameraService::config('system')` -> `getSystemInfo()` | Message **1020**, `Name = SystemInfo`. |
| `CameraApi.config(camera, section)` for `capabilities` | `CameraService::config('capabilities')` -> `getSystemFunction()` | Message **1360**, payload `{"Name":"SystemFunction","SessionID":"0x<session>"}`. |
| `CameraApi.config(camera, section)` for other sections | `CameraService::config()` -> `DvripClient::getConfig(command)` | Message **1042**, payload `{"Name":"<command>","SessionID":"0x<session>"}`. Commands are listed below. |
| `CameraApi.config(camera, 'all')` | `CameraService::all()` -> each `config(section)` | Sequential getter commands for all 14 sections; failures are captured per section. |

Read-only section-to-command mapping:

| UI section | DVRIP getter |
|---|---|
| General | `1042 / Name=General` |
| Network | `1042 / Name=NetWork` |
| Camera / image | `1042 / Name=Camera` |
| Detection | `1042 / Name=Detect` |
| Encoding | `1042 / Name=Simplify.Encode` |
| Advanced encoding | `1042 / Name=AVEnc` |
| Recording | `1042 / Name=Record` |
| Storage | `1042 / Name=Storage` |
| Alarms | `1042 / Name=Alarm` |
| UART / RS-485 / PTZ | `1042 / Name=Uart` |
| OSD and volume | `1042 / Name=fVideo` |
| Capabilities | `1360 / Name=SystemFunction` |
| System | `1020 / Name=SystemInfo` |
| Ability | `1042 / Name=Ability` |

## SMTP form

| JavaScript call | PHP method/function | Raw DVRIP command |
|---|---|---|
| `CameraApi.smtp(camera)` | `CameraService::smtp()` -> `smtpBranch()` -> `getConfig('NetWork.NetEmail')` | Message **1042**, `{"Name":"NetWork.NetEmail","SessionID":"0x<session>"}`. |
| `CameraApi.saveSmtp(camera, values)` | `CameraService::updateSmtp()` | Three commands: **1042 get** `NetWork.NetEmail`; **1040 set** payload shown below; **1042 get** the same branch for exact verification. |
| `CameraApi.testSmtp(camera)` | `CameraService::testSmtp()` -> `getSystemFunction()` -> `smtpBranch()` -> `DvripClient::testMail()` | Check `SupportMailTest`; get **1042** `NetWork.NetEmail`; then send **1636** `Name = OPMailTest` with the saved email settings. No configuration setter is sent. |

The true configuration-set payload shape is:

```text
DVRIP request message: 1040
Payload:
{
  "Name": "NetWork.NetEmail",
  "SessionID": "0x<session>",
  "NetWork.NetEmail": <complete freshly-read branch with owned fields merged>
}
Expected response message: 1041
```

The test operation uses the camera's saved email settings in this payload shape
(secret values are represented symbolically):

```text
DVRIP request message: 1636
Payload:
{
  "Name": "OPMailTest",
  "SessionID": "0x<session>",
  "OPMailTest": {
    "Enable": true,
    "MailServer": <saved SMTP server object>,
    "UseSSL": <saved value>,
    "SendAddr": "<saved sender>",
    "Recievers": ["<saved recipients>"],
    "Schedule": <saved schedule>,
    "Title": "<saved subject>"
  }
}
Expected response message: 1637
```

## FTP connection test

| JavaScript call | PHP method/function | Raw DVRIP command |
|---|---|---|
| `CameraApi.testFtp(camera)` | `Api::handle('ftp-test')` -> `CameraService::testFtp()` -> `DvripClient::testFtp()` | POST-only; check `SystemFunction.OtherFunction.SupportFTPTest`; **1042 get** `NetWork.NetFTP`; **2008** `Name = OPFTPTest`, `OPFTPTest = <saved FTP object>`, current `SessionID`; expect response **2009**. No configuration setter is sent. |

The app displays camera rejection as `ftp_test_failed`; the raw camera code
is not exposed in the browser. Balcon returned DVRIP `Ret=109` on
2026-10-07. A server-side capture showed successful FTP login followed by
`550 Create directory operation failed` when the camera attempted `MKD Test`
at the FTP login root (`local_root=/` on `bbb`), which the FTP user cannot
write. The saved upload directory is not used by this test. The FTP test is
not an event-upload test. Request and
response message IDs/payload were cross-checked against the
[DVRIP/Sofia FTP-test capture](https://github.com/KostasEreksonas/DVRIP_Sofia_reference_codes/blob/main/payloads/2008_OPFTPTest_request.json).

## Generic dedicated editors

| JavaScript call | PHP method/function | Raw DVRIP command |
|---|---|---|
| `CameraApi.editor(camera, editorId)` | `CameraService::editor()` -> `NetworkServiceEditor::get()` -> `readBranch()` -> `getConfig(branch)` | Message **1042**, `Name = <allow-listed branch>`. Wi-Fi also reads `NetWork.NetDHCP`. |
| `CameraApi.saveEditor(camera, editorId, values)` | `CameraService::updateEditor()` -> `NetworkServiceEditor::update()` | Normally: **1042 get branch** -> **1040 set complete merged branch** -> **1042 read-back**. Wi-Fi may additionally get/set/read `NetWork.NetDHCP` for `eth1.Enable`. |

For `main_stream` and `extra_stream`, opening the editor additionally requests
the camera's encoding capability so the Codec and Resolution selects only
offer values advertised for that stream. Saving re-reads this capability and
rejects unsupported changed values before issuing a setter:

| JavaScript call | PHP method/function | Raw DVRIP command |
|---|---|---|
| `CameraApi.editor(camera, 'main_stream' or 'extra_stream')` | `NetworkServiceEditor::get()` -> `DvripClient::getEncodeCapability()` -> `getNamed()` | Message **1360**, `Name = EncodeCapability`; matching `EncodeInfo[]` `CompressionMask` and `ResolutionMask` are decoded into human-labeled selects with exact DVRIP values. |
| `CameraApi.saveEditor(camera, 'main_stream' or 'extra_stream', values)` | `NetworkServiceEditor::update()` -> `DvripClient::getEncodeCapability()` before `setConfig()` | Message **1360**, `Name = EncodeCapability`; selected codec/resolution must be in the stream's advertised masks (or equal its current value), otherwise no message **1040** is sent. |

Every generic configuration write has this exact shape:

```text
DVRIP request message: 1040
Payload:
{
  "Name": "<allow-listed branch>",
  "SessionID": "0x<session>",
  "<allow-listed branch>": <complete freshly-read branch with owned fields merged>
}
Expected response message: 1041
```

Editor ID to true DVRIP `Name` mapping:

| Editor IDs | DVRIP branch |
|---|---|
| `ftp` | `NetWork.NetFTP` |
| `ntp` | `NetWork.NetNTP` |
| `upnp` | `NetWork.Upnp` |
| `pms` | `NetWork.PMS` |
| `das` | `NetWork.DAS` |
| `multicast` | `NetWork.Multicast` |
| `pppoe` | `NetWork.NetPPPoE` |
| `rtsp` | `NetWork.RTSP` |
| `serial_bridge` | `NetWork.TranCommData` |
| `nat` | `NetWork.Nat` |
| `dhcp` | `NetWork.NetDHCP` |
| `wired_network` | `NetWork.NetCommon` (plus `NetWork.NetDHCP` for mirrored `eth0.Enable`) |
| `wifi` | `NetWork.Wifi` (plus `NetWork.NetDHCP` for mirrored `eth1.Enable`) |
| `wifi_signal` | `NetWork.WifiStrengthLevel` |
| `vendor_connections` | `NetWork.DisableXmServerConn` |
| `online_upgrade` | `NetWork.OnlineUpgrade` |
| `ddns_1` … `ddns_5` | `NetWork.NetDDNS` (one array item owned per page) |
| `general_preferences` | `General.General` |
| `locale_formats` | `General.Location` |
| `auto_maintain` | `General.AutoMaintain` |
| `ptz_resume` | `General.ResumePtzState` |
| `network_watchdog` | `NetWork.CheckNetState` |
| `ip_adaptive` | `NetWork.IPAdaptive` |
| `network_order` | `NetWork.NetOrder` |
| `alarm_server_1` … `alarm_server_5` | `NetWork.AlarmServer` (one array item owned per page) |
| `defog` | `Camera.ClearFog` |
| `white_light` | `Camera.WhiteLight` |
| `distortion` | `Camera.DistortionCorrect` |
| `fisheye` | `Camera.FishLensParam` |
| `image_basic` | `Camera.Param` |
| `image_advanced` | `Camera.ParamEx` |
| `detection_tracking` | `Detect.DetectTrack` |
| `human_detection` | `Detect.HumanDetection` for detection/rule fields, including first-rule `PedRule[0].RuleLine.Pts` (Tripwire) and `PedRule[0].RuleRegion.Pts` (Intrusion); `Detect.MotionDetect.[0]` for Send Email (`EventHandler.MailEnable`), App Push (`EventHandler.MessageEnable`), FTP upload (`EventHandler.FTPEnable`, only with `NetServerFunction.NetFTP`), Write log (`EventHandler.LogEnable`, only when present as Boolean), event snapshot (`EventHandler.SnapEnable` plus channel-one bit of `SnapShotMask`), Alarm Sound (`EventHandler.VoiceEnable`, only where speaker support is indicated), sound selection (`EventHandler.VoiceType`, choices read from `Ability.VoiceTipType.VoiceTip[].VoiceEnum`), and Period (`EventHandler.TimeSection[day][slot]`) |
| `motion_detection` | `Detect.MotionDetect` |
| `blind_detection` | `Detect.BlindDetect` |
| `video_loss_detection` | `Detect.LossDetect` |
| `vehicle_detection` | `Detect.CarShapeDetection` |
| `main_stream`, `extra_stream` | `Simplify.Encode` (one format/stream owned per page) |
| `snapshot_image` | `AVEnc.Encode[0].SnapFormat[0..2]`: regular, motion-detection, and alarm snapshot profiles. The editor owns only `VideoEnable` plus video `Resolution`, `Quality`, `BitRate`, and `BitRateControl`; codec, FPS, GOP, audio, and vendor-specific fields are preserved. Resolution choices are the union of camera-advertised main/extra encoder resolution masks, with each current snapshot value retained as a fallback. |
| `smart_h264` | `AVEnc.SmartH264` |
| `video_color_day`, `video_color_night` | `AVEnc.VideoColor` (one profile owned per page) |
| `video_widget` | `AVEnc.VideoWidget` |
| `overlay_indicators` | `fVideo.GUISet` |
| `osd_logo` | `fVideo.OsdLogo` |
| `output_volume` | `fVideo.Volume` |
| `input_volume` | `fVideo.VolumeIn` |
| `snapshot` | `Storage.Snapshot` (Balcon direct read/write); Door rejects direct read, so use `Storage` parent read, fresh-read/merge/full-parent-write/read-back verification. Door parent-write path is unit-tested but not yet live-write-tested. |
| `recording` | `Record` |
| `storage_failure` | `Storage.StorageFailure` |
| `storage_low_space` | `Storage.StorageLowSpace` |
| `storage_missing` | `Storage.StorageNotExist` |
| `alarm_output` | `Alarm.AlarmOut` |
| `local_alarm` | `Alarm.LocalAlarm` |
| `network_abort_alarm` | `Alarm.NetAbort` |
| `network_alarm` | `Alarm.NetAlarm` |
| `ip_conflict_alarm` | `Alarm.NetIPConflict` |
| `uart_comm_1` | `Uart.Comm` |
| `ptz_serial` | `Uart.PTZ` |
| `rs485` | `Uart.RS485` |

## Human Detection shape editor

`renderRuleSection()` in `rule-form.js` -> `renderServiceForm()` ->
`CameraApi.saveEditor(camera, 'human_detection', payload)` ->
`Api::handle('editor-save')` -> `CameraService::updateEditor()` ->
`NetworkServiceEditor::update('human_detection', payload)` ->
`DvripClient::getConfig('Detect.HumanDetection')` (**1042**) ->
`DvripClient::setConfig('Detect.HumanDetection', merged full branch)` (**1040**)
-> `DvripClient::getConfig('Detect.HumanDetection')` (**1042** for verification).
The optional `ruleGeometryChanges` payload contains original and edited points.
PHP rejects stale originals and invalid coordinates before the write. It owns
only first-rule line/region points; unrelated shape, direction, and rule values
survive the fresh-read merge.

`rule-form.js` image load/Refresh image -> `stuff/php/snapshot.php?camera=...`
-> configured camera `GET /webcapture.jpg?command=snap&channel=0` -> JPEG.
This still-image path is camera HTTP, **not a DVRIP command**, and does not
write settings. The SVG uses the JPEG's current rendered bounds to map mouse
positions to camera coordinates `0..8192`.

The owner confirmed on 2026-10-07 that saved Tripwire and Intrusion geometry
matches the native CMS view and that browser resizing keeps the overlay aligned.

## Human Detection PTZ activation

`renderServiceForm()` PTZ subsection -> `CameraApi.saveEditor(camera,
'human_detection', payload)` -> `Api::handle('editor-save')` ->
`CameraService::updateEditor()` -> `NetworkServiceEditor::update()` ->
`DvripClient::getConfig('Detect.MotionDetect.[0]')` (**1042**) ->
`DvripClient::setConfig('Detect.MotionDetect.[0]', merged full branch)`
(**1040**) -> `DvripClient::getConfig('Detect.MotionDetect.[0]')` (**1042**
verification). The owned raw fields are
`EventHandler.PtzLink[0] = ["None"|"Preset"|"Tour"|"Pattern", number]` and
`EventHandler.PtzEnable` (set true when activating a link). Other channels,
settings, and the existing enable flag when selecting None are preserved.
`SystemFunction.OtherFunction.SupportPTZDirectionControl` plus action-specific
capability flags gate the form; Door does not advertise these and has no PTZ
controls. The generic ONVIF preset enumeration is *not* used as evidence of
saved presets. On 2026-10-08, live Balcon `Preset/1`, `Tour/1`, and `Pattern/1`
writes through this route each survived a fresh camera read with Human Detection
temporarily disabled. The original `None/0` link and detection state were
restored and verified. No alert-triggered PTZ movement was tested; a known
saved slot is still required for that test.
The vendor's [configuration format](https://git.pblr-nyk.pro/mirror/python-dvr/raw/commit/81acf8845d7892be5b9ecb1ee92ebbe0a1c3ef5a/doc/%E9%85%8D%E7%BD%AE%E4%BA%A4%E6%8D%A2%E6%A0%BC%E5%BC%8FV2.0.pdf)
lists all four action strings and describes the second element as the
corresponding preset, tour, or pattern number. It gives no 255 limit; the
web form therefore requires a positive number for newly selected active
actions without asserting an upper bound. None normalizes to `["None", 0]`.

## Live PTZ control

`ACCESS -> Live` -> `CameraApi.ptzCapabilities(camera)` ->
`Api::handle('ptz')` -> `CameraService::ptzCapabilities()` ->
`DvripClient::getSystemFunction()` (**1360**, `Name=SystemFunction`). Real
controls appear only for `OtherFunction.SupportPTZDirectionControl=true`;
generic ONVIF service or preset responses are not sufficient.

A held direction or zoom button sends one POST at a time:
`CameraApi.ptzCommand(camera, {operation:"move", ...})` ->
`Api::handle('ptz-command')` -> `CameraService::ptzCommand()` ->
`OnvifPtz::pulse()`. The pulse performs authenticated Media `GetProfiles`, PTZ
`ContinuousMove`, a bounded wait, and PTZ `Stop` from `finally`. The current
camera-reported ONVIF port and the Balcon-advertised `/onvif/Media` and
`/onvif/PTZ` paths are used. The browser never receives camera credentials.

Preset POSTs use the same API route and `DvripClient::ptzPreset()`:

```text
DVRIP request message: 1400
Name: OPPTZControl
Command: GotoPreset | SetPreset | ClearPreset
Parameter.Preset: 1..255
```

The camera accepts numeric slots but cannot reliably enumerate which ones are
saved. Save and delete require browser confirmation; recall does not.

## Wi-Fi access-point search

`CameraApi.wifiAccessPoints(camera)` -> `CameraService::wifiAccessPoints()` ->
`DvripClient::getInfo('WifiAP')`

```text
DVRIP request message: 1020
Payload:
{
  "Name": "WifiAP",
  "SessionID": "0x<session>"
}
Expected response message: 1021
Response data is taken from the "WifiAP" array.
```

The JavaScript `Use` button is local only: `wifiValuesFromAccessPoint()` copies
the selected AP's readable values into the form. It does not contact the camera
until the user reviews and saves the form.

## Reboot

`CameraApi.reboot(camera)` -> `Api::handle('reboot')` ->
`CameraService::reboot()` -> `DvripClient::reboot()`

```text
DVRIP request message: 1450
Payload:
{
  "Name": "OPMachine",
  "SessionID": "0x<session>",
  "OPMachine": {
    "Action": "Reboot"
  }
}
Expected response message: 1451
```

After a successful response the PHP request holds the TCP connection open for
12 seconds before closing it, which is required for reliable reboot behaviour
on this firmware.

## Browser HTTP action map

The browser endpoints are all `stuff/php/api.php`:

- GET: `cameras`, `status`, `sections`, `config`, `smtp`, `editors`, `editor`,
  `wifi-aps`.
- POST: `smtp-save`, `smtp-test`, `ftp-test`, `editor-save`, `reboot`.

`api.php` serializes camera-facing actions with a per-camera lock under
`/var/tmp/php-upload`. This is request serialization, not a background worker.
