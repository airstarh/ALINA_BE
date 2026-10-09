# CMS select investigation — 2026-10-09

Scope: English labels only; other translations are deferred until requested.
Screenshots are in `ai/img/selects/`. These are vendor wire-value mappings,
not claims that the camera API returns a complete enumeration of choices.
Existing unfamiliar values remain selectable so opening/saving a form does
not silently replace them.

## Implemented mappings

| App form / field | Choices and camera values | Evidence |
| --- | --- | --- |
| Image controls / White balance | Automatic `0x00000000`, Indoor `0x00000001`, Outdoor `0x00000002` | CMS screenshots 14-12-34 / 14-15-00; SDK dialog loads/saves combo index directly |
| Image controls / Slow shutter | None `0x00000000`, Low `0x00000002`, Medium `0x00000004`, High `0x00000006` | Screenshot 14-51-38; SDK dialog divides stored value by two when loading and multiplies index by two when saving |
| Image controls / IR-cut | IR synchronous switch `0`, Automatic switch `1` | SDK header and screenshots 14-12-53 / 14-14-23 |
| Image controls / Day and night noise filters | `0`–`5`, zero means no filtering | SDK header; screenshots 14-11-39 / 14-14-14 |
| Locale / Language | English; preserve any existing other value | English-only project scope |
| Locale / Date format | `YYMMDD`, `MMDDYY`, `DDMMYY` | SDK structure comments and English language resources |
| Locale / Separator | `.`, `-`, `/` | SDK structure comments |
| Locale / Time format | `12`, `24` | SDK structure comments and English resources |
| Locale / Video standard | `PAL`, `NTSC` | SDK documentation; changing standard can affect stream frame rates |

Dropdown mappings are applied both when loading and after saving.
Balcon and Door both expose `EsShutter` and `WhiteBalance`; read-only checks
found Balcon High/Outdoor and Door Low/Automatic respectively.

## Screenshot routing and remaining work

- 12-57-44, 13-00-02, 13-00-16: Main/Extra video stream — CBR/VBR,
  I-frame interval, bitrate. Existing stream dropdown work remains in place;
  codec/resolution use camera capabilities while fallback numeric choices are
  application presets, not an exact CMS capability list.
- 13-49-29: Locale and display formats.
- 14-10-35 / 14-13-32: Image controls — exposure mode and minimum/maximum
  exposure times. SDK times are microseconds, CMS displays milliseconds.
  Combined exposure preset mapping is not implemented yet.
- 14-10-58 / 14-13-42: Auto iris; 14-11-08 / 14-13-52: BLC.
  Screenshot labels alone are insufficient to add new wire-value mappings.
- 14-11-26 / 14-14-02: Automatic gain — already a checkbox in Image controls.
- 14-12-44 / 14-14-32: Defog — existing separate Defog form.
- 14-14-45: DWDR; 14-14-53: Image style; 14-15-18: Day/night mode.
  Exact new-firmware enum mappings remain to be confirmed before adding choices.
- 14-16-12: Locale DST rule, start/end transitions — requires a coordinated
  editor; existing raw rule field and transition data are preserved.

## Static evidence

Inspected files, without executing the installer:

- `/tmp/alina-balcon-activex/English.lang` (UTF-16 English resource labels).
- `/tmp/VideoPlayControl/dll_Libary/XMVideoSDK/netsdk.h` (GB18030).
- `/tmp/VideoPlayControl/dll_Libary/XMVideoSDK/ConfigModule.dll`
  (older unpacked configuration module).

Narrow disassembly evidence in that older module:
white-balance load `0x1023a3f7`, save `0x1023af7a`;
slow-shutter load `0x1023a55d`, save `0x1023aff2`–`0x1023affd`.
The newer downloaded module is packed, so its labels alone were not treated
as proof of numeric values. Temporary artifacts are not repository dependencies.

## Limited positive verification

On Balcon, changed white balance to Automatic and date separator to `/` via
the application editor implementation; each value and returned dropdown was
verified by read-back, then every original form value was restored and verified.
PHP syntax checks passed. No full suite, SPA build, PTZ movement, or orientation
change was performed. Browser rendering has not been visually verified in this pass.

## Follow-up investigation (read-only)

No application behaviour or camera setting was changed in this pass.

### Exposure can be camera-specific

Read-only DVRIP request **1360**, `Name: Camera`, succeeds on both Balcon and
Door. The response contains `Count: 8` and these `Speeds`, in microseconds:

| Speed | CMS label |
| --- | --- |
| `0x00004E20` = 20000 | Manual 1/50 |
| `0x0000208D` = 8333 | Manual 1/120 (rounded microsecond duration) |
| `0x00000FA0` = 4000 | Manual 1/250 |
| `0x000007D0` = 2000 | Manual 1/500 |
| `0x000003E8` = 1000 | Manual 1/1000 |
| `0x000001F4` = 500 | Manual 1/2000 |
| `0x000000FA` = 250 | Manual 1/4000 |
| `0x00000064` = 100 | Manual 1/10000 |

The older CMS inserts Automatic first, then capability-derived exposure
choices. It loads/saves `ExposureParam.Level` as the combo index (Automatic
is zero). When saving manual mode, it clears `LeastTime` and `MostTime`;
automatic mode uses the entered minimum/maximum times. Therefore this needs
a coordinated editor, not merely independent dropdowns on all three fields.
Evidence: ConfigModule load `0x1023a3d8`, save `0x1023af52`, manual time reset
`0x1023ae90` / `0x1023aea4`. Existing exposure settings were not altered.

The speculative capability name `CameraPARAM` returns code 103 on Balcon;
do not use it instead of the successful `Camera` request.

### Additional confirmed SDK mappings

- `Camera.Param.[0].ApertureMode`: hex zero = Close, hex one = Open.
- `Camera.Param.[0].BLCMode`: hex zero = Close, hex one = Open.
  Both combos explicitly assign item data 1 to Open and 0 to Close, and
  load/save against the corresponding SDK struct offsets. Evidence:
  ConfigModule combo construction `0x102395f9`–`0x10239773`, field comparisons
  `0x1023a44c` (BLC) / `0x1023a4b1` (aperture).
- DWDR is `Camera.ParamEx.[0].BroadTrends.AutoGain`, using 0/1 for Close/Open;
  its limit is `BroadTrends.Gain`. The current Gain control on Image controls
  is a different setting (`Camera.Param.GainParam`), not the DWDR limit.
- Image-style serializer table contains `typedefault` (enum 0), `type1`
  (enum 1), `type2` (enum 2). Evidence: NetSdk table `0x100f4148`, exchange
  routine `0x100a7e81`. Both cameras currently return `type1`, matching the
  provided Balcon screenshot's Style2. Style1→typedefault and Style3→type2
  are consistent with SDK enum ordering, but those two UI labels have not
  been independently confirmed on this firmware.
- Older SDK day/night ordering is 0 Automatic, 1 Full color, 2 Black-and-white
  (ConfigModule construction `0x102394b6`–`0x102395ce`, direct-index load
  `0x1023a4d9`). Balcon currently uses hex 3 while its screenshot shows Smart
  Alert. Its screenshot calls the first option Star IR rather than Automatic;
  that newer-firmware label mapping remains unconfirmed.
- SDK header also specifies ParamEx `Dis`, `Ldc`, `LowLuxMode`,
  `CorridorMode`, `PreventOverExpo` as 0 off / 1 on; automatic-adjustment level
  1–4; soft photosensitive control 0 sensor automatic, 1 software automatic,
  2 on, 3 off. These are SDK definitions, not per-camera capability claims.

### DST structure

NetSdk's serializer table `0x100f421c` confirms `DSTRule` is `Off` / `On`.
`DSTStart` and `DSTEnd` each have Year, Month, Week, Day, Hour, Minute;
exchange routine `0x100a93d0` confirms these JSON field names.
The SDK describes Week 0 as date-based, 1–4 as first–fourth week and -1 as
last week; in week-based mode Day is weekday, Sunday 0 through Saturday 6.
Date-based Day semantics and newer CMS mode switching still deserve one
focused capture before enabling DST editing. Keep the existing transition
objects unchanged until that is confirmed; simply turning DST On using stale
transition dates could change camera timestamps unexpectedly.

### Smallest useful user-assisted capture, if needed

One CMS capture switching Day/night from Smart Alert to Star IR and back,
plus one capture changing the DST Date/Day-of-week mode and restoring it,
would settle the remaining firmware-specific meanings. There is no need for
movement, a detection walk, a broad test suite, or random setting probes.

### User-assisted Day/Night confirmation

The owner saved Star IR in Balcon CMS, and a read-only API check returned
`Camera.Param.[0].DayNightColor: "0x00000000"`. The owner then restored Smart
Alert; a second read-only check returned `"0x00000003"`.
This confirms Star IR = 0 and Smart Alert = 3 on Balcon, resolving the newer
firmware label ambiguity. No capture script or application-driven camera write
was needed.

Screenshots in `ai/img/01/` also show both DST layouts: Day of Week contains
month, week occurrence, weekday, hour and minute; Date contains a calendar date,
hour and minute. The displayed year is 2026 while the API returned 2027 for
both transition objects with DST Off. That discrepancy remains unresolved;
the screenshots alone are not proof of the stored transition year.

## Implementation after approval

Added Day/Night, Auto iris, BLC, exposure mode and automatic timing limits to
Image controls; DWDR and its gain limit to Advanced image controls. The new
fields are shown only when their configuration paths exist. Exposure choices
come from request 1360 `Camera`, with the current value preserved if unknown;
mode editing is disabled when capabilities cannot be obtained. Timing inputs
are numeric microseconds (not hex strings); they follow the Automatic/Manual
selection. Changing to a manual preset clears automatic limits as CMS does,
while saving other fields in an unchanged manual mode preserves existing times.

Focused checks only: two new PHP positive tests and one UI selection test
were observed failing before implementation and passing afterward. On Balcon,
the editor returned the eight expected presets and DWDR fields. A BLC Open
save/read-back passed, followed by exact restoration of the entire original
Camera.Param branch (including flip and mirror). PHP syntax and diff checks
passed. No full suite, SPA build, DST write or PTZ movement was performed.
