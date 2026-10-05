# IP camera investigation

This directory contains sanitized investigation notes for the local IP cameras.
Credentials must remain in `_GITOUT/addon/cam/creds.md` and must not be copied
into this directory, application source, command examples, or browser-side code.

## Safety rules

- Prefer passive or read-only protocol operations.
- Probe only a specifically selected camera; do not scan the whole subnet.
- Do not change network, user, encoding, imaging, PTZ, or event settings during
  discovery.
- Do not invoke reboot, reset, backup/restore, firmware, or factory-default
  operations.
- Treat an advertised capability as a claim until a read-only request verifies it.
- Record mutations separately and require explicit user confirmation before they
  are attempted.

## Investigations

- [Balcon camera](balcon-discovery.md)
- [Balcon vendor configuration inventory](balcon-vendor-config.md)
- [Balcon configuration SPA design](2026-10-05-cam-spa-design.md)
- [Balcon configuration SPA implementation plan](2026-10-05-cam-spa-implementation-plan.md)
- [Camera SPA verification](cam-spa-verification.md)
