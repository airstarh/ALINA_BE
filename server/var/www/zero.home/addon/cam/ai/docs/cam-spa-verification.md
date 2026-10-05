# Camera configuration SPA verification

Date: 2026-10-05 (Europe/Moscow)

## Result

The standalone, getter-only Balcon configuration application is implemented
under `server/var/www/zero.home/addon/cam`. It uses native PHP for DVRIP and
native browser modules for the SPA. It does not use the ALINA API, Composer,
npm, Python at runtime, or a JavaScript build step.

The ignored `_GITOUT/addon/cam/config.php` contains the Balcon registry and
private connection values. The public camera-list endpoint returned only the
stable ID `balcon`, display name `Balcon`, and default ID. Host, port, username,
password, and URI were absent.

## Live Balcon results

The PHP client logged in to Balcon over DVRIP/TCP and returned sanitized system
status. All allow-listed read-only sections succeeded:

- General
- Network
- Camera/image
- Detection
- Encoding
- Advanced encoding
- Recording
- Storage
- Alarms
- UART/RS-485/PTZ configuration
- OSD and volume
- Capabilities
- System information
- Ability

No setter, PTZ action, reboot, firmware, account, alarm-subscription, recording,
or storage operation is implemented or sent.

The live probe revealed that Balcon responds with DVRIP header version 1 while
requests use version 0. The decoder now accepts response versions 0 and 1, and
the captured 20-byte header shape is covered by a credential-free regression
test.

An unknown camera request returned HTTP 404. A subsequent valid Balcon status
request returned HTTP 200, confirming recovery without restarting the backend.

## Browser verification

The page was rendered with Chromium 154.0.8037.57 at 1440×1000 and 390×844.
Both layouts showed:

- Balcon selected by default.
- No credential input.
- A single Connect action before connection.
- A readable responsive dark layout.
- No configuration dashboard before Connect.

The visual check found and corrected a CSS rule that initially overrode the
dashboard's `hidden` attribute. Final desktop and narrow-screen screenshots are
stored in ignored files:

- `_GITOUT/addon/cam/cam-spa-desktop.png`
- `_GITOUT/addon/cam/cam-spa-mobile.png`

The session's interactive browser-control surface was unavailable, so clicking
through every section was not UI-automated. The same flow is covered at its
boundaries by browser-module tests and live HTTP checks: default selection,
Connect request construction, request cancellation, stale-response rejection,
all section getters, error envelopes, and recovery.

## Automated verification

Environment:

- PHP CLI 8.5.4; application syntax remains PHP 8.2 compatible.
- Node.js 20.20.2.
- Chromium 154.0.8037.57 snap.

Commands:

```bash
php server/var/www/zero.home/addon/cam/stuff/tests/run.php
node --test server/var/www/zero.home/addon/cam/stuff/tests/ui.test.mjs
find server/var/www/zero.home/addon/cam -type f -name '*.php' -print0 |
    xargs -0 -n1 php -l
git diff --check
git check-ignore -v _GITOUT/addon/cam/config.php
```

At final verification time the PHP suite contained 42 passing tests. This
includes regressions for the live header version, packet identity, reconnecting
after transport timeouts, continuing after missing sections, recursive URI and
token sanitization, configuration error envelopes, and total fragmented-read
deadlines. The Node suite passed. PHP syntax checks and tracked-file
credential-pattern checks were clean.

## Serving note

The existing local nginx instance returned HTTP 404 for `/addon/cam/`, which
indicates that it is not currently serving this source checkout at that URL.
No nginx, Docker, deployment, mount, or permission configuration was changed.
End-to-end verification used a temporary localhost-only PHP development server
rooted directly at the camera application directory, and that server was
stopped afterward.
