# Final 37-editor batch

This batch completes the planned Balcon configuration interface. It adds:

- maintenance, PTZ resume, network watchdog, adaptive IP, and network priority;
- five pages backed by the camera's alarm-server array;
- distortion, fisheye, basic image, and advanced image controls;
- motion, blind, video-loss, and vehicle detection;
- main stream, extra stream, Smart H.264, two color profiles, and video title;
- snapshot and recording controls;
- storage-failure, low-space, and missing-storage event handling;
- alarm output, local alarm, network disconnect, network alarm, and IP conflict;
- UART communication, PTZ serial, and RS-485 configuration.

Together with Email / SMTP and the previous 30 schema-driven pages, this makes
68 dedicated editor pages.

## Save behavior

The pages reuse the established allow-listed editor engine. The browser chooses
an editor ID, never an arbitrary DVRIP command or field path. A save validates
the submitted scalar values, reads the latest complete branch, merges only the
fields owned by that page, writes the complete branch, and requires an exact
read-back match. This behavior was later changed: editable password/key fields
show their current value in JavaScript, and blank password fields clear the
camera value.

Schedules, masks, detection geometry, PTZ links, and other complex data not
shown on a page remain in the complete branch and are preserved during saves.
Connectivity-, stream-, image-, alarm-, reboot-, and serial-sensitive pages
display warnings.

## Live verification

On 2026-10-05 all 37 new editor models were loaded through the production PHP
API from the Balcon camera. Every branch and field path matched the live V5
firmware response. These checks used GET requests only and changed no camera
setting.

## Deliberate exclusions

The raw explorer remains the place to inspect capability, status, null,
hardware-limit, and internal definition-table nodes. Account changes, firmware
execution, reboot, reset, factory defaults, and similar commands are not
ordinary configuration forms and remain outside the generic editor catalogue.
