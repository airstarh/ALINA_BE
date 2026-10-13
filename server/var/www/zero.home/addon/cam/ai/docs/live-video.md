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

## Resource implications

Each active viewer occupies one PHP-FPM worker and one FFmpeg process. Because
the video is copied rather than transcoded, CPU use should remain modest, but
the long-lived request still consumes worker capacity. This is suitable for the
application's small-admin usage model and should be revisited before supporting
many concurrent viewers.
