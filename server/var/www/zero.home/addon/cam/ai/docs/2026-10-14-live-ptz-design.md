# Live PTZ control design

Date: 2026-10-14

## Purpose

Add practical PTZ controls to the existing **ACCESS -> Live** page so the
owner can watch Balcon while moving it and managing numeric preset positions.
This first version favors bounded movement and explicit stopping over a broad
implementation of every control shown by the legacy CMS.

Directions describe the physical camera axes. The application does not reverse
commands to compensate for the camera's configured Mirror or Flip settings.

## Supported scope

The first version provides:

- eight physical pan/tilt directions;
- zoom in and zoom out;
- Low, Medium, and High movement speeds;
- repeated movement while a control remains pressed;
- recall of a positive numeric preset slot;
- save/overwrite of the current position in a numeric preset slot;
- deletion of a numeric preset slot.

It does not provide tours, patterns, focus, iris, home position, or absolute
coordinates. The camera cannot reliably enumerate saved presets: its ONVIF
`GetPresets` response is a generic 127-entry list even on physically static
Door. The form therefore accepts a user-supplied positive slot and does not
claim that the slot exists.

## Capability gating

Controls are returned only when live camera capability includes
`SystemFunction.OtherFunction.SupportPTZDirectionControl=true`. Preset mutation
also requires `SupportSetPTZPresetAttribute=true`. Door advertises neither and
must not receive an actionable PTZ interface.

The server re-checks capability for every command; hiding controls in the
browser is not authorization. Unknown cameras, commands, directions, speed
values, durations, and preset numbers are rejected before a motor command is
sent.

## Protocols

Movement uses the Balcon ONVIF PTZ service because four directions were
physically verified with `ContinuousMove` followed by explicit `Stop`.
Movement and Stop use the existing ONVIF credentials or fall back to the DVRIP
camera account, and the camera's configured ONVIF port.

Preset actions use DVRIP message 1400, `OPPTZControl`, with the established
vendor commands `GotoPreset`, `SetPreset`, and `ClearPreset`. Direct
`GotoPreset` for the owner's saved slots 1-5 was previously verified on
Balcon. ONVIF is not used to decide which preset slots exist.

## Motor-safety behavior

One POST request represents one short movement pulse. The server sends ONVIF
`ContinuousMove`, waits only for the validated bounded pulse duration, and
sends ONVIF `Stop` from a `finally` path. Thus an in-flight request still stops
the motor if the browser releases the button, navigates away, or disconnects.

The browser begins another pulse only after the previous request completes.
Pointer release, pointer cancellation, window blur, page hiding, navigation,
and view destruction stop the repeat loop. This prevents queued stale movement
requests. A visible Stop control ends repetition; the server-side stop guarantee
does not depend on that button.

Saving or deleting a preset requires browser confirmation because it overwrites
or removes camera state. Going to a preset does not require confirmation.

## Components and data flow

`ptz` GET action:

1. Login to the selected camera through the existing serialized API path.
2. Read `SystemFunction` and derive an allow-listed capability model.
3. Return only supported controls and safety limits; return unavailable for
   Door or any camera without real PTZ flags.

`ptz-command` POST action:

1. Validate the requested operation and its operation-specific fields.
2. Re-read camera capability and reject unsupported or forged operations.
3. For movement, call the ONVIF PTZ service and guarantee Stop after the bounded
   pulse.
4. For presets, issue the matching DVRIP `OPPTZControl` command.
5. Return a small success response without exposing credentials or raw packets.

The existing per-camera request lock serializes PTZ commands with other DVRIP
configuration operations. Live video uses its separate FFmpeg request and
continues while a PTZ command runs.

The Live renderer loads PTZ capability alongside playback, renders controls
only when supported, and calls the same camera API endpoint as the rest of the
application. PTZ failure appears beside the controls and does not terminate the
video stream.

## Error handling

- Every mutation is POST-only.
- Credentials remain server-side and are excluded from responses and logs.
- ONVIF faults and DVRIP rejection become stable public errors.
- A failed Stop is reported prominently; subsequent movement is disabled until
  the PTZ section is reloaded.
- Preset save/delete success means the camera accepted the command. Since this
  firmware cannot reliably list saved slots, the application does not claim an
  independent preset read-back verification.

## Verification

Automated tests cover:

- exact DVRIP preset command payloads;
- ONVIF movement and unconditional Stop ordering;
- command validation and POST enforcement;
- capability gating that exposes Balcon and hides Door;
- non-overlapping press/hold pulses and all browser stop conditions;
- confirmation requirements for save and delete;
- preset controls not claiming enumeration or existence.

Syntax checks, the complete PHP test suite, the complete browser-JavaScript
test suite, and `git diff --check` run before hand-off. Automated verification
does not move the physical camera. The owner performs the final directional,
zoom, recall, save, and delete checks while watching the live page.
