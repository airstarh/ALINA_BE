# Image Upload `ERR_ACCESS_DENIED` — Handoff (2026-10-29)

## Current status

An ordinary image upload from the tale editor failed in the browser. Investigation paused until the user updates and reboots the PC.

## Evidence collected

- Browser DevTools showed `POST https://zero.home:50443/FileUpload?isAjax=1` failing with `net::ERR_ACCESS_DENIED`, followed by `TypeError: Failed to fetch`.
- The failed request showed zero response bytes and failed in about 5 ms.
- The corresponding request did not appear in Nginx access logs, and there was no recent PHP/application error for it.
- Nearby requests to the same host succeeded, including tale file-list requests, tale save, and bulk file deletion.
- No new file database row or physical upload appeared for the failed attempt.

This places the failure before the request reached Nginx. The exact cause is not confirmed. A local browser/file-access restriction is one possibility; do not treat it as established without another reproduction or additional browser diagnostics.

## Relevant frontend path

- `ALINA_FE/src/components/elements/form/AlinaFileUploader.vue`
  - `onChangeFileField()` routes non-video files through `uploadRequest()`.
  - `uploadRequest()` sends the files through `AjaxAlina` as `multipart/form-data` to `/FileUpload`.
- `ALINA_FE/src/services/Ajax.js`
  - `multipart/form-data` bodies are created by `UtilsData.objectToFormData()`.
- `ALINA_FE/src/Utils/UtilsData.js`
  - Converts file arrays into `FormData` entries.

The video upload path also uses this multipart request machinery but posts to `/FileUpload/video`. It succeeded in earlier tests; that does not yet explain why this ordinary image request was denied locally.

## Resume checklist after PC reboot

1. Retry the same ordinary image upload and note whether it still fails.
2. If it fails, capture the failed Network request details and Console output, including the selected file's name, size, and MIME type. Do not share credentials or cookies.
3. Check whether the failure depends on the file or browser (for example, retry a small newly captured image and another browser/profile).
4. Compare the exact request URL, multipart payload construction, and any browser extension/privacy tooling if the failure persists.
5. Only change frontend or backend code after the failing layer is identified. Current server evidence does not support a backend fix.

No code change has been made for this image-upload failure.
