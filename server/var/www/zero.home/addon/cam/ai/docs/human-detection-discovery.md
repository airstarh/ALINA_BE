# Human Detection feature discovery

Updated: 2026-10-07 (Europe/Moscow)

## Goal

Recreate the legacy CMS `Alarm -> Intelligent Alert` workflow as a coherent
web feature. Screenshots supplied by the owner show a main alert page, a rule
geometry dialog, a weekly period editor, and a PTZ-activation dialog. Door is
physically static: its generic legacy-CMS PTZ controls must not be treated as
evidence of motor/PTZ support or exposed as usable controls.

## Application behavior and discovery history

- The `human_detection` editor exposes top-level detection/display values plus
  the first rule's enable state and verified rule type. After the native CMS
  captures below, it also edits Send Email, App Push, and (where advertised)
  Alarm Sound and the weekly Period. It now also edits the first rule's
  Tripwire or Intrusion geometry; PTZ activation remains unimplemented.
- Existing event editors share a schema for basic event routing, but their
  notes explicitly leave event schedules, masks, PTZ links, and region geometry
  read-only. They do not currently represent Human Detection's full CMS flow.
- The read-only `Detect` branch contains `HumanDetection.PedRule`, including
  four rule slots with enabled state, line points, region points, direction,
  and rule type. The first rule's line/region point conventions were mapped
  by the captures below; the other rule slots remain untouched.
- The live `Detect.HumanDetection` object includes `EventHandler` and
  `TimeSection` keys, but both values are `null`. The related
  `Detect.HumanDetectionDVR` object also returns `EventHandler=null`, so the
  CMS notification and period screens are not mapped to populated values in
  these branches.

## Read-only live observations

The production app's existing GET API was used for `Detect` and `Alarm` on Door
and Balcon. No setting writes or reboot actions were made.

- Door returns `Detect.HumanDetection` with four `PedRule` entries. The first
  rule is enabled; the other three are disabled. Rule geometry is present and
  uses coordinate values around 100..8100. Other scalar values include
  `Enable=true`, `Sensitivity=1`, `PushInterval=3000`, and `PedFdrAlg=1`.
- Balcon returns the same overall shape and four rules. Its first rule is
  enabled and has non-default geometry; its algorithm is `0`.
- Door's `SystemFunction` response contains no PTZ support flags. The live
  capability query yielded no PTZ-related capability paths for Door.
- Balcon reports `SupportPTZDirectionControl`, `SupportPTZTour`,
  `SupportResumePtzState`, and `SupportSetPTZPresetAttribute` as true.
- Neither camera's exposed `Alarm` config section contains an Intelligent Alert
  node. Door's generic alarms include event-handler structures for other alarm
  classes, but that is not evidence that they control Human Detection.
- A follow-up shape-only check confirmed `HumanDetection[0].EventHandler =
  null`, `HumanDetection[0].TimeSection = null`, and
  `HumanDetectionDVR[0].EventHandler = null` on both cameras. The `Alarm` tree
  includes named PEA/human-detection placeholders whose values are `[null]`.
  Other alarm classes have populated event handlers, but are separate branches
  and cannot safely stand in for Human Detection.
- Locked, getter-only DVRIP ConfigGet probes on both devices confirmed
  `Detect.HumanDetection` and no other useful candidate among
  `PEAInHumanPed`, `HumanPedDetection`, `HumanDection`, `MotionHumanDection`,
  `AlarmConfig`, `[Link].[0]`, or alarm/detection-prefixed variants. Those
  candidates returned the camera's missing-config response. `[Link]` returned
  `[null]`, not a populated event-link record. The temporary probe endpoint
  used for these calls was removed after the investigation; no new diagnostic
  API remains in the application.
- The local XM `NetSdk.dll` strings exposed additional candidate names
  (`Analyze.Pea`, `PEARule`, `IntelPEA`, `AlgorithmPEA`, and `LimitPEA`). A
  temporary fixed-name, getter-only probe through the per-camera lock returned
  `device_config_missing` for all five names and their `Analyze.*` variants on
  both cameras. That temporary route was removed; no diagnostic route remains.
- A second getter-only pass tried the same fixed candidate names using DVRIP
  message 1020 (`GetInfo`) as well as message 1042 (`ConfigGet`). Every 1020
  candidate returned the device-error response on both cameras. This did not
  identify another settings branch. The temporary diagnostic endpoint was
  removed after the pass; no probe route remains in the application.

## Initial getter-only investigation (before native CMS capture)

Getter-only probes did not identify the CMS Human Detection schedule,
notification/event routing, or PTZ activation commands. The later native CMS
captures below identified the three notification checkboxes. Do not guess
remaining names, enum values, or setter structures. The generic legacy UI presents
PTZ widgets even for Door and is not a reliable hardware-capability source.

The vendor capability dictionary distinguishes `PEAInHumanPed` as human
detection joined to perimeter tripwire detection. This clarifies the native
Rule dialog's Tripwire/Intrusion context, but it does not document a DVRIP
configuration branch or prove Door supports PTZ. See the vendor's
[SystemFunction capability reference](https://docs.jftech.com/?lang=zh&menusId=1&siderid=310dab0dab3a46f2b9a937f1c73a5dcb).

The app API currently exposes only its fixed read-only section catalogue;
the shell's local PHP CLI cannot use the production per-camera lock because
`/var/tmp/php-upload` is not present in that shell's filesystem. Do not bypass
the per-camera lock for further probes. The candidate probes ran through a
temporary endpoint inside the locked production request path and have been
removed. Continue discovery through a similarly constrained, temporary probe
or through a sanitized analysis of a native-CMS DVRIP exchange that does not
disclose credentials or change settings. For the CMS exchange, the useful
minimum is request/response message ID, command name, and redacted structure;
omit login packets, passwords, SSIDs, addresses, tokens, and full config
payloads.

## Implementation direction after mapping

Once the exact branches are verified, present the user with one Human Detection
page that groups detection/rules, active periods, and event actions. Preserve
the current camera's full branches on save and write/read-back each actual
DVRIP branch through the established serialized API. PTZ activation must be
shown only when live device capability indicates real PTZ support; Door must
never show an actionable PTZ editor, regardless of generic legacy-CMS controls.

## Follow-up getter probes and initial implementation (2026-10-06)

A getter-only pass through the app's locked production request path tested both
cameras. `Detect.HumanDetection.[0]` and its `PedRule` child returned populated
data. Candidate paths `Detect.HumanDetectionDVR` (including indexed and
EventHandler children), `PEA` under `Detect`, `VideoAnalyze`, and `Alarm`, and
related candidate alarm/human names returned null or the device's missing-config
response. Message 1020 (`GetInfo`) candidates also returned device errors. The
temporary fixed-name probe endpoint was removed afterwards; no probe route
remains. At that point, no schedule/action path had been identified.

The local cached XM SDK header at
`/tmp/VideoPlayControl/dll_Libary/XMVideoSDK/netsdk.h` defines
`SDK_ANALYSE_TRIPWIRE = 0` and `SDK_ANALYSE_PERIMETER = 1`. Both cameras report
`PedRule[0].RuleType = 1`, and the supplied CMS screenshot shows Intrusion
selected. Together, the SDK enum and live value/screenshot corroborate the
selector mapping `0 = Tripwire`, `1 = Intrusion area`; the UI offers no other
values.

The SDK `NetSdk.dll` also contains the string `Analyze.Pea`, along with generic
event-handler member names such as `MailEnable`, `MsgtoNetEnable`, and
`BeepEnable`. Getter-only ConfigGets for `Analyze.Pea` and the related PEA
names above returned `device_config_missing` on both cameras. These strings
are a useful lead but do not establish this firmware's DVRIP JSON mapping.

The Human Detection page initially exposed a toggle for the first rule and the
verified Tripwire/Intrusion selector. Saving fresh-reads the complete
`Detect.HumanDetection` branch, merges its allow-listed fields, writes the full
branch, and verifies it by reading back. The later App Push addition is
described below and writes a separate branch only when its value changes.
Rule geometry, the other three slots, sensitivity, object/algorithm codes, and
all other values are preserved and not offered as unverified inputs. Existing
top-level detection/display controls remain available. Automated tests verify
the merge and read-back behavior; no live camera setting was changed for this
UI implementation.

At that stage, the Period, geometry, and PTZ activation editors remained
unimplemented. PTZ activation remains absent, particularly for physically
static Door.

## Native CMS capture: App Push mapping (2026-10-06)

The owner captured a Door CMS session in `/tmp/door-human-detection.pcap`
while changing only App Push and then restoring it. The capture contains a
ConfigGet (message 1042) of `Detect.MotionDetect.[0]`, followed by
`Detect.HumanDetection.[0]` and `Ability.VoiceTipType`. The CMS saved
`Detect.MotionDetect.[0]` via message 1040 on both clicks; both responses
were successful (1041, `Ret=100`). A structural comparison of the two saves
found exactly one changed value:

```text
Detect.MotionDetect.[0].EventHandler.MessageEnable: true -> false
```

The first save turned App Push on, and the second restored it off. The
current app exposes this field on Human Detection as a Boolean App Push
control, but its true DVRIP owner is the motion-alert EventHandler. The app
fresh-reads this branch, merges only `MessageEnable`, writes it only when
changed, and verifies read-back. This setting may be shared with ordinary
motion alerts; the form warns about that linkage. The PCAP itself is a local
temporary capture that may contain credentials and must not be copied into
tracked documentation.

The same EventHandler contains `MailEnable`, `VoiceEnable`, and `BeepEnable`,
but this Door capture did not toggle Send Email or Alarm Sound. Schedule,
geometry, and PTZ activation remain unmapped. Door is physically static,
regardless of the generic CMS PTZ dialog.

## Native CMS capture: Balcon Alarm Sound mapping (2026-10-06)

The owner captured Balcon in `/tmp/balcon-alarm-sound.pcap` while turning only
Alarm Sound on and then restoring it. The CMS again used ConfigGet 1042 for
`Detect.MotionDetect.[0]` and `Detect.HumanDetection.[0]`, plus
`Ability.VoiceTipType`. It saved `Detect.MotionDetect.[0]` twice using 1040;
both 1041 responses had `Ret=100`. The complete structural diff of the two
saves was exactly:

```text
Detect.MotionDetect.[0].EventHandler.VoiceEnable: true -> false
```

`BeepEnable` remained false on both writes. This proves the CMS Alarm Sound
checkbox is `VoiceEnable` on Balcon, not `BeepEnable`. The web form now exposes
it under Human Detection and merges it into the same freshly-read full branch
as App Push, with write/read-back verification. No camera setting was changed
by this web implementation.

Door's `Ability.VoiceTipType` returns a list despite the owner's confirmation
that Door cannot play sound; a voice-tip list alone is not a speaker test.
Balcon advertises `OtherFunction.SupportCloseVoiceTip=true`, whereas Door
does not. The app currently uses that observed extra capability as a
conservative automatic gate for the Alarm Sound control. It may hide a future
speaker-equipped camera that omits this flag; it is not universal physical
speaker detection. The capture is kept only under `/tmp` and may contain
credentials; do not copy the PCAP into tracked docs.

## Native CMS capture: Door Send Email mapping (2026-10-06)

The owner's first two Door captures contained only DVRIP KeepAlive requests.
The third, `/tmp/door-send-email-3.pcap`, captured the CMS opening Intelligent
Alert, saving Send Email on, reopening, then restoring it off. The CMS read
`Detect.MotionDetect.[0]`, `Detect.HumanDetection.[0]`, and
`Ability.VoiceTipType`, then saved `Detect.MotionDetect.[0]` twice using
message 1040. Both 1041 replies had `Ret=100`. The complete structural diff
of the two saves was exactly:

```text
Detect.MotionDetect.[0].EventHandler.MailEnable: true -> false
```

The app now offers Send Email on the Human Detection form. It writes only
`MailEnable` inside a freshly-read full action branch and verifies read-back.
This toggle is distinct from, and does not change, `NetWork.NetMail` SMTP
settings. The capture remains local under `/tmp` because it may contain
credentials; do not copy it into tracked docs.

## Native CMS capture: Door Period mapping (2026-10-06)

The owner changed Tuesday's first enabled interval from `00:00–24:00` to
`00:00–23:59` in the legacy CMS, saved, then restored it. The capture
`/tmp/door-period.pcap` contains two successful message-1040 saves of
`Detect.MotionDetect.[0]`; comparing their complete named objects found only:

```text
EventHandler.TimeSection[2][0]:
  "1 00:00:00-23:59:00" -> "1 00:00:00-24:00:00"
```

Door and Balcon both expose a seven-row, six-slot `TimeSection` matrix in
this action branch. The capture confirms that day index 2 is Tuesday and slot
index 0 is the first interval. The leading `1` denotes an enabled interval;
the existing disabled slots begin with `0`. The CMS writes the whole
`Detect.MotionDetect.[0]` object, so Period is shared with the other alert
actions rather than stored under `Detect.HumanDetection`.

The web Human Detection page now has a collapsible Period section, one day at
a time, showing each camera slot. It sends only edited slots and their
original raw values. PHP fresh-reads the full action branch, rejects a changed
slot if it no longer matches its original value, merges only submitted slots,
writes the full branch when changed, and verifies read-back. Time input accepts
`HH:MM` or `HH:MM:SS`; `24:00` is allowed only as an end time. Invalid or
unknown schedule shapes are not exposed for editing. No automated tests or
live web save were run for this Period addition at the owner's request; PHP
and JavaScript syntax and `git diff --check` were checked.

## Balcon rule geometry captures (2026-10-07)

The owner captured two separate legacy-CMS saves on Balcon. The first capture,
`/tmp/balcon-intrusion-20261007-074551.pcap`, contains one successful write
of `Detect.HumanDetection.[0]` at 07:48:45. Its enabled first `PedRule` had
`RuleType=1` (Intrusion), `RuleLine.Pts=(54,21)->(8056,8019)`, and
`RuleRegion.Pts=[(3899,0),(7784,108),(7512,7111),(3735,8083)]`.

The second capture, `/tmp/balcon-intrusion-second-20261007-075454.pcap`,
contains a later successful write of the same branch. Its first rule has
`RuleType=0` (Tripwire), `RuleLine.Pts=(3722,86)->(4021,8170)`, and
`RuleRegion.Pts=[(3885,0),(7770,86),(7499,7089),(3722,8062)]`. Both
responses returned `Ret=100`; a subsequent read-only web API read-back
matched the second write exactly. The CMS stores both line and polygon
geometry even though `RuleType` selects only one active rule kind. The second
save therefore did **not** leave Intrusion active; ask the owner to check the
CMS radio selection before assuming the displayed polygon is the active rule.

These captures do not establish a CMS gesture for deleting a saved polygon.
The PCAPs remain only under `/tmp` and may contain credentials; do not copy
their raw contents into tracked docs.

## Balcon physical rule test (2026-10-07)

The owner captured a short walk test in
`/tmp/balcon-rule-test-20261007-082939.pcap`. The CMS successfully saved
`Detect.HumanDetection.[0]` as Tripwire (`RuleType=0`) at 08:29:58, then as
Intrusion (`RuleType=1`) at 08:31:54; both replies had `Ret=100`. The camera
sent `AlarmInfo` / `appEventHumanDetectAlarm` Start/Stop pairs at
08:31:03/08:31:04 (camera `StartTime=08:31:02`) and
08:32:28/08:32:37 (camera `StartTime=08:32:27` / `08:32:36`). These events
occurred while Tripwire and Intrusion respectively were active. A later
read-only config read confirmed Intrusion remains active. This proves both
rule kinds can trigger on Balcon, but one walk each does not establish the
precise position/direction/timing threshold.

The owner's Thunderbird screenshot showed camera message list times of
03:31/03:32 while the Balcon event body and DVRIP event times showed
08:31/08:32 local. The owner provided the raw headers for one Balcon message:
its `Date` was `Wed, 07 Oct 2026 08:31:04 +0800`, while Gmail's `Received`
timestamp was `Tue, 06 Oct 2026 22:31:06 -0700 (PDT)`. The latter equals
08:31:06 at the owner's UTC+3 location, matching the event body and packet
capture. The mail's `Date` instead represents 03:31:04 at UTC+3, exactly the
time Thunderbird displayed. Thus Gmail receipt and Thunderbird's conversion
are correct; the camera-originated message labels its 08:31 clock reading
with the wrong `+0800` offset. Why that offset is generated remains unproven.
Both cameras currently report `NetWork.NetNTP.TimeZone=14` and NTP enabled;
do not reinterpret or change that code without evidence. Mail addresses and
other raw headers are deliberately omitted from this tracked note.

## Integrated rule editor (2026-10-07)

The Human Detection form now has a collapsed `Detection rule / area` section.
Tripwire is one draggable two-endpoint line. Intrusion is one four-sided region;
the corners and sides are movable. There is no delete operation: `Discard shape
edits` resets only the unsaved browser draft. Saving uses the existing Review
changes flow. It transmits optional `ruleGeometryChanges` with the original
point arrays; PHP fresh-reads the branch, rejects stale originals, checks point
range and shape validity, merges only the changed `RuleLine.Pts` or
`RuleRegion.Pts`, then verifies DVRIP read-back. The inactive shape, direction
flags, other PedRule slots, and unrelated settings are preserved.

Coordinates are the camera's normalized integer grid `0..8192`, not JPEG
pixels. The SVG is stretched over the image's current rendered bounds and
pointer locations are converted from its actual bounding box. Hence a change
of camera resolution does not require rewriting stored rule coordinates. A
fresh still image comes from `webcapture.jpg?command=snap&channel=0` through
the same-origin PHP JPEG endpoint `stuff/php/snapshot.php`; both Balcon and Door
returned JPEGs in read-only checks. Neither camera's live rule was changed by
the implementation verification. PTZ activation remains unmapped.

### Owner's end-to-end check

On 2026-10-07 the owner tested both the polygon and Tripwire in our browser
editor, saved changes, and compared them against the native CMS/ActiveX rule
page. The shapes appeared exactly the same there. Mouse editing worked, and
the overlay remained correctly scaled when the browser window changed size.
This verifies the current image-to-rule coordinate mapping in actual use.
It does not yet prove behavior after changing the camera's encoding resolution
itself; keep that as a separate test rather than conflating it with browser
resize.

## Event snapshot switch (2026-10-07)

The owner identified the native CMS `Snapshot` control as important for
including a detection image with alert email. The local XM SDK header's
`SDK_EventHandler` has `bSnapEn` and `dwSnapShot` members. Existing CMS
captures show `Detect.MotionDetect.[0].EventHandler.SnapEnable=true` paired
with `SnapShotMask=0x00000001000000000000000000000000`. Fresh read-only
queries found Balcon `false`/all-zero and Door `true`/channel-one mask. This
supports, but does not directly prove by a single-control capture, the native
UI mapping. The screenshot's small `1` is a channel selector/mask rather than
a plain Boolean checkbox in the observed schema.

Our Human Detection form now exposes `Include event snapshot (shared alert
action)` when both fields have the recognized shape. It edits the first
channel bit and `SnapEnable` in the fresh-read shared
`Detect.MotionDetect.[0]` branch and verifies the complete branch after the
write. Other channel bits and unrelated alert settings are preserved. SMTP
destination and `MailEnable` are separate; an image attachment still requires
Send Email to be enabled. Automated tests cover enable, disable, other-channel
preservation, and an invalid mask. Live GET-only checks showed the control on
both cameras, false on Balcon and true on Door. No live camera change or
received email attachment was tested by this implementation; the owner should
verify the latter with one real detection event.

## FTP and Write log action switches (2026-10-07)

The native CMS Intelligent Alert screenshot includes FTP and Write log
checkboxes in addition to Send Email, App Push, and Alarm Sound. Both live
cameras expose Boolean `Detect.MotionDetect.[0].EventHandler.FTPEnable` and
`LogEnable`; both advertise `SystemFunction.NetServerFunction.NetFTP=true`.
The Human Detection form now discovers FTP only when both the Boolean field
and the FTP capability are present, and discovers Write log when its Boolean
field is present. It fresh-reads and merges these fields into the complete
shared action branch, then reads back the write. The FTP server/directory is
still configured on the separate FTP page. This work was verified with unit
tests and read-only live form requests for Balcon and Door; neither camera's
action settings were changed during implementation. The names and shape align
with the CMS controls, but a one-checkbox CMS packet capture for each field
has not yet been made.
