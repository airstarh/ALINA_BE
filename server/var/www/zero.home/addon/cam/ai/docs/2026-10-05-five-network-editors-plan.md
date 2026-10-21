# Five Safe Network Editors Implementation Plan

**Goal:** Add dedicated editors for FTP, NTP, UPnP, PMS, and DAS while preserving the existing raw configuration explorer.

**Architecture:** A server-side allow-listed schema maps friendly form fields to exact DVRIP paths within one configuration branch. Every save validates typed input, reads the latest complete branch, merges owned fields, preserves hidden fields and blank passwords, writes only that branch, and verifies exact read-back. One reusable native-JavaScript renderer presents each schema as a dedicated form with review-before-save.

**Constraints:** No arbitrary DVRIP branch names; no camera IP/login/encoding/recording changes; no password returned to the browser; existing per-camera request lock; no JavaScript build; no Git commit.

## Forms

- FTP → `NetWork.NetFTP`
- NTP → `NetWork.NetNTP`
- UPnP → `NetWork.Upnp`
- PMS push service → `NetWork.PMS`
- DAS service → `NetWork.DAS`

## Tasks

1. Add failing PHP tests for allow-listing, typed models, password preservation, full-branch merge, validation, and read-back mismatch.
2. Implement the server-side network editor schema and expose GET/POST JSON actions under the existing camera lock.
3. Add failing UI tests for editor GET/POST calls and typed form payload construction.
4. Implement the five menu entries and reusable dedicated form renderer.
5. Run the full PHP/UI suites, syntax checks, live read-only GET checks, and document the result.
