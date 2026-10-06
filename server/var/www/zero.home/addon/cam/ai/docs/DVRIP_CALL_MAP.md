# DVRIP call map

Last updated: 2026-10-06 (Europe/Moscow)

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
| `CameraApi.editors(camera)` | `Api::handle('editors')` -> `CameraService::editorList()` -> `NetworkServiceEditor::list()` | **None.** Static PHP allow-list; current API returns before login. |

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
| `human_detection` | `Detect.HumanDetection` for detection/rule fields; `Detect.MotionDetect.[0]` for native-CMS Send Email (`EventHandler.MailEnable`), App Push (`EventHandler.MessageEnable`), Alarm Sound (`EventHandler.VoiceEnable`, only where voice-tip control is advertised), and Period (`EventHandler.TimeSection[day][slot]`) |
| `motion_detection` | `Detect.MotionDetect` |
| `blind_detection` | `Detect.BlindDetect` |
| `video_loss_detection` | `Detect.LossDetect` |
| `vehicle_detection` | `Detect.CarShapeDetection` |
| `main_stream`, `extra_stream` | `Simplify.Encode` (one format/stream owned per page) |
| `smart_h264` | `AVEnc.SmartH264` |
| `video_color_day`, `video_color_night` | `AVEnc.VideoColor` (one profile owned per page) |
| `video_widget` | `AVEnc.VideoWidget` |
| `overlay_indicators` | `fVideo.GUISet` |
| `osd_logo` | `fVideo.OsdLogo` |
| `output_volume` | `fVideo.Volume` |
| `input_volume` | `fVideo.VolumeIn` |
| `snapshot` | `Storage.Snapshot` |
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
- POST: `smtp-save`, `editor-save`, `reboot`.

`api.php` serializes camera-facing actions with a per-camera lock under
`/var/tmp/php-upload`. This is request serialization, not a background worker.
