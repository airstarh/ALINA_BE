# Live video

The camera application provides an inline **ACCESS -> Live** page. The first
supported camera is Balcon.

## Data path

Balcon RTSP -> FFmpeg in the PHP 8.2 container -> fragmented MP4 over HTTPS ->
the browser's native Media Source Extensions player.

FFmpeg copies the compressed video stream (`-c:v copy`); it does not decode or
re-encode it. Audio is omitted. Camera credentials remain in server-side
`config.php` and are never included in browser URLs or FFmpeg error output.

The endpoint probes the selected stream with FFprobe, reports the codec and
dimensions, and starts playback only when the browser confirms native support.
The current Balcon main stream is HEVC, so playback depends on the browser and
host system supporting HEVC through Media Source Extensions.

## Runtime behaviour

- Live is the default page after a camera connects, and Balcon playback starts
  automatically when the page is opened. Stop remains explicit, while Start
  allows a manual reconnect after a stop, timeout, or error.
- Leaving the page, changing camera, refreshing the form, or hiding the page
  aborts the browser request and terminates its FFmpeg child process.
- One Balcon viewer is permitted at a time in this PHP runtime.
- The player follows the newest buffered data and removes old buffered media to
  keep delay and browser memory bounded.
- A stream has a server-side safety deadline just below the PHP/FastCGI timeout;
  press Start again if that deadline is reached.
- Door and other cameras are intentionally disabled until their stream is
  positively verified.

The former workstation-only `127.0.0.1:18765` prototype and all of its files
were removed. Live video now runs through the installed FFmpeg and FFprobe
binaries in the PHP 8.2 container; it does not require a separate local helper.

## Live PTZ controls

When the selected camera reports
`SystemFunction.OtherFunction.SupportPTZDirectionControl=true`, the Live page
shows eight physical pan/tilt directions, zoom in/out, and Low, Medium, and
High speeds beside the video. Door reports no such capability, so it receives
an explanation instead of actionable controls. Mirror and Flip image settings
do not reverse the commands: the arrows refer to the camera's physical axes.

Holding a movement control sends serialized 180 ms ONVIF `ContinuousMove`
pulses. Each backend pulse attempts `Stop` from a `finally` path, even if the
move fails or the browser disconnects. Releasing/cancelling the pointer,
pressing the central Stop button, changing/hiding the page, blurring the
window, or destroying the view prevents another pulse. If the camera reports
that Stop itself failed, all movement buttons are disabled until the page is
reloaded. Live video continues independently when a PTZ request fails.

Balcon also advertises preset mutation, so the page accepts a numeric slot for:

- **Go to** — recalls the slot without confirmation;
- **Save current position** — confirms before overwriting the slot;
- **Delete preset** — confirms before deleting the slot.

Preset actions use vendor DVRIP `OPPTZControl` message 1400 with
`GotoPreset`, `SetPreset`, or `ClearPreset`. This firmware returns a generic
127-entry ONVIF preset list even on static Door, so the page deliberately does
not list or claim that any numeric slot exists. Use only slots intentionally
created and tested by the operator.

### Physical verification checklist

Automated tests never move the motors. While watching Live, the owner should:

1. Select Low speed and briefly press at least two opposite directions; verify
   physical movement and release-stop behavior.
2. Briefly test Zoom + and Zoom −, then return to the desired balcony view.
3. Use **Go to** for known slots 1–5 and confirm the expected saved views.
4. Save a new expendable slot, move away, recall it, then delete that slot.
5. If any movement continues after release, use the central Stop control and
   leave the Live page; do not issue another command until the cause is known.

## Resource implications

Each active viewer occupies one PHP-FPM worker and one FFmpeg process. Because
the video is copied rather than transcoded, CPU use should remain modest, but
the long-lived request still consumes worker capacity. This is suitable for the
application's small-admin usage model and should be revisited before supporting
many concurrent viewers.
