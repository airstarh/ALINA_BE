# Xiongmai Alarm Center / `NetWork.AlarmServer`

Reviewed 2026-10-07. This note distinguishes observed camera configuration from
vendor documentation and unverified software/protocol details. No camera or
server setting was changed for this investigation. Do not put server credentials
or complete camera configuration responses in this file.

## What it is

The camera's five **Alarm server** pages are five destination slots for an
external alarm receiver. They are not five alarm categories, video-storage
targets, FTP servers, or NAS shares. Each slot contains `Enable`, `Alarm`,
`Log`, `Protocol`, and a `Server` object with name/address, port, username, and
password. The camera can be configured to report alarms and optionally logs to
such a receiver. This is separate from SMTP email, the detection event's Send
Email action, FTP uploads, and MotionEye recording.

The Xiongmai-family camera manuals call the receiver **AlarmCenter** and show
an older Windows utility with a listening port, Start/Stop buttons, and an
event table. One manual explicitly says the AlarmCenter service software must
be installed on the target server before using this setting. Thus merely
entering an IP address of an ordinary PC, FTP server, or network share is not
enough.

## Local observations

- Read-only `NetWork.AlarmServer` responses from both **Balcon** and **Door**
  contain five entries. All five had `Enable=false` when checked on 2026-10-07.
- Slot 1 on each camera had `Protocol=GENERAL` and port `15002`; the remaining
  slots had `Protocol=NONE` and the same prefilled port. These appear to be
  default/template values, **not evidence of an active connection**. The
  `Alarm=true` flag in a disabled slot does not itself enable reporting.
- Our web app maps these fields to five separate editor pages in
  `stuff/php/ExtendedEditorDefinitions.php`. We have not enabled a slot or
  captured an AlarmCenter report from either camera.

## Protocol and software availability

- The manuals label the camera-side protocol `GENERAL` and commonly show port
  `15002`. `GENERAL` is a vendor alarm-center selection, not HTTP, SMTP, FTP,
  ONVIF, or a documented generic syslog endpoint. The exact on-wire format,
  transport (TCP versus UDP), authentication behavior, and compatibility of
  other receivers with these exact firmware builds remain **unverified**.
- The XMEye/NetSDK documentation exposes
  `H264_DVR_StartAlarmCenterListen(port, callback, userData)` and a matching
  stop function. Its alarm-center configuration structures provide five slots
  and alarm/log flags. This establishes a vendor SDK path for writing a
  receiver, but does not by itself establish the wire protocol or provide a
  tested ready-to-run Linux service.
- Historical manuals show a ready-to-run **Windows AlarmCenter** utility, and
  Xiongmai advertises a `myeyeSDK` download. We have **not** verified a current
  AlarmCenter binary download, its license or price, whether it is bundled in
  that SDK, or a maintained Linux package. Do not describe it as confirmed
  freeware/open source or promise a turnkey Kubuntu installation.

## Conclusion for this homelab

Leave AlarmServer disabled unless we deliberately deploy a compatible
receiver. Existing SMTP alerts and MotionEye do not require it. If we decide
to use it, first obtain/inspect the actual vendor utility or SDK package,
verify its license and supported OS, then test a receiver against one camera
with a controlled event and packet capture. Keep this separate from the
camera's ordinary DVRIP control connection on TCP 34567 and its SMTP/FTP
features.

## Sources

- [Xiongmai-family manual: Alarm center software and AlarmServer settings](https://vidau-cctv.ru/upload/iblock/5c0/5c0b9ce51538a473d8cf3ee15fe5b3bc.pdf)
- [Camera manual: Alarm center reporting, `GENERAL`, port 15002](https://bxilie.obs.cn-east-3.myhuaweicloud.com/English/Software/WEB/WEB/IP%20Camera%20user%20manual_Common%20Version_.pdf)
- [XMEye NetSDK: alarm-center listener API](https://github.com/xmeye/openplatform-docs/blob/master/docs/NetSDKWindows/NetSDKWindows-InterfaceFunctionUsage.md)
- [XMEye NetSDK: configuration structures and five server slots](https://github.com/xmeye/openplatform-docs/blob/master/docs/NetSDKWindows/NetSDKWindows-enumerationandstruction.md)
- [Xiongmai `myeyeSDK` download listing](https://www.xiongmaitech.com/en/index.php/service/down_detail/83/210)
- Local read-only DVRIP responses for Balcon and Door on 2026-10-07; relevant
  field mapping: `stuff/php/ExtendedEditorDefinitions.php`.
