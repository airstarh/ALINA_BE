# Human Detection feature discovery

Updated: 2026-10-06 (Europe/Moscow)

## Goal

Recreate the legacy CMS `Alarm -> Intelligent Alert` workflow as a coherent
web feature. Screenshots supplied by the owner show a main alert page, a rule
geometry dialog, a weekly period editor, and a PTZ-activation dialog. Door is
physically static: its generic legacy-CMS PTZ controls must not be treated as
evidence of motor/PTZ support or exposed as usable controls.

## Existing application behavior

- The `human_detection` editor exposes top-level detection/display values plus
  the first rule's enable state and verified rule type. After the native CMS
  captures below, it also edits Send Email, App Push, and (where advertised)
  Alarm Sound and the weekly Period. It does not edit rule geometry or PTZ
  actions.
- Existing event editors share a schema for basic event routing, but their
  notes explicitly leave event schedules, masks, PTZ links, and region geometry
  read-only. They do not currently represent Human Detection's full CMS flow.
- The read-only `Detect` branch contains `HumanDetection.PedRule`, including
  four rule slots with enabled state, line points, region points, direction,
  and rule type. Do not change these structures until the wire values and
  coordinate conventions are mapped.
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
