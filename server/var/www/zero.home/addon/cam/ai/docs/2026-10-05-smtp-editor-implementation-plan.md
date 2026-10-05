# SMTP Editor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the first dedicated writable camera form for `NetWork.NetEmail`, with password-preserving read/merge/write/read-back behavior.

**Architecture:** Extend the native PHP DVRIP client with configuration writes, expose a narrow SMTP-only JSON API, and render a typed SMTP form in the existing build-free JavaScript application. The raw configuration explorer remains available. A save obtains the camera's latest complete SMTP object, merges validated user fields, writes the complete object, then reads it back and returns a sanitized verified result.

**Tech Stack:** PHP 8.2, native DVRIP TCP framing, browser ES modules, native HTML/CSS, PHP and Node test runners.

**Spec:** Approved visual direction “B · Dedicated form” from the 2026-10-05 camera design session; protocol evidence is in `balcon-vendor-config.md`.

## Global Constraints

- Work only on the configured Balcon camera for this first form.
- Never expose camera or SMTP passwords to the browser.
- A blank SMTP password means preserve the camera's current password.
- Save only `NetWork.NetEmail`, not the entire network or camera configuration.
- Serialize write requests with the existing per-camera lock.
- Do not create a Git commit.
- Do not run a JavaScript SPA build; this application is build-free.

## Review Focus

- Empty password preserves the current camera password during the full-branch write.
- Malformed or incomplete SMTP objects are rejected before any write.
- Boolean and integer fields retain the DVRIP types expected by the camera.
- A rejected write or mismatched read-back returns an error instead of false success.
- Rapid UI actions cannot overlap or update a stale camera selection.

---

### Task 1: DVRIP configuration writer

**Files:**
- Modify: `stuff/php/DvripReader.php`
- Modify: `stuff/php/DvripClient.php`
- Test: `stuff/tests/DvripClient.test.php`

**Interfaces:**
- Produces: `DvripReader::setConfig(string $command, mixed $value): mixed`

- [ ] Write failing tests for message `1040`, full named payload, successful response, and rejected response.
- [ ] Run the focused tests and confirm RED.
- [ ] Implement the writer using the existing authenticated request machinery.
- [ ] Run focused and complete PHP suites and confirm GREEN.

### Task 2: SMTP service and API contract

**Files:**
- Modify: `stuff/php/CameraService.php`
- Modify: `stuff/php/api.php`
- Test: `stuff/tests/CameraService.test.php`
- Test: `stuff/tests/Api.test.php`

**Interfaces:**
- Produces: `CameraService::smtp(): array`
- Produces: `CameraService::updateSmtp(array $input): array`
- Produces: API actions `smtp` (GET) and `smtp-save` (POST JSON)

- [ ] Write failing tests for sanitized reads, validation, password preservation, typed merge, full-branch write, and verified read-back.
- [ ] Run focused tests and confirm RED.
- [ ] Implement the narrow service and request parser; include both SMTP actions in the camera lock.
- [ ] Run focused and complete PHP suites and confirm GREEN.

### Task 3: Dedicated SMTP browser form

**Files:**
- Create: `stuff/js/smtp-form.js`
- Modify: `stuff/js/api.js`
- Modify: `stuff/js/app.js`
- Modify: `stuff/css/app.css`
- Modify: `index.php`
- Test: `stuff/tests/ui.test.mjs`
- Modify: `ai/docs/README.md`
- Modify: `ai/docs/balcon-vendor-config.md`

**Interfaces:**
- Consumes: `smtp` and `smtp-save` JSON actions.
- Produces: a dedicated Email / SMTP menu item with load, edit, reload, review, save, and result feedback.

- [ ] Write failing browser-module tests for form values, password omission, and POST JSON API behavior.
- [ ] Run UI tests and confirm RED.
- [ ] Implement the typed form and integrate it without removing the raw Network view.
- [ ] Run PHP and UI suites, syntax checks, and `git diff --check`; document the final write path.
