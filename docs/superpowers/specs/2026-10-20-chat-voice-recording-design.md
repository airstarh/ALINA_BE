# Chat Voice Recording Design

## Goal

Allow an authenticated participant on phone or desktop to record and send a playable voice message with two microphone-button presses. Recordings use the existing ALINA file-upload path and persist as ordinary chat messages containing a media reference.

## User interaction

- A microphone button appears beside the existing attachment button.
- The button follows the existing localized icon convention: `i_mic` while idle and an active/red microphone treatment while recording.
- First click requests microphone access and starts recording.
- Second click stops recording, uploads it, and immediately sends the resulting voice message.
- The recording stops and auto-sends when it reaches five minutes.
- There is no preview or separate confirmation step.
- Recording does not alter the composer draft.
- Starting a recording is disabled while disconnected, uploading, already stopping/sending, or unauthenticated.
- The mic follows the existing attachment policy and remains disabled for guests because `/FileUpload` requires an authenticated user.
- Leaving/changing the chat or unmounting the component stops all microphone tracks and discards an unfinished recording.

## Browser recording

Vue obtains an audio-only stream with `navigator.mediaDevices.getUserMedia({audio: true})` and records with `MediaRecorder`.

The client selects the first supported format using `MediaRecorder.isTypeSupported()`:

1. `audio/webm;codecs=opus`, file extension `.webm`;
2. `audio/ogg;codecs=opus`, file extension `.ogg`;
3. `audio/mp4`, file extension `.m4a`.

If none is supported, recording is unavailable and the UI reports an error. The recorder emits chunks; on stop, Vue creates a `Blob`, wraps it in a named `File`, and uploads that file. Empty recordings are rejected.

The five-minute timer calls the same stop/send path as the second button press. The stop path must be idempotent so a user click at the timeout boundary cannot upload or send twice.

## Upload and message flow

The recording uses the existing authenticated `POST /FileUpload` multipart flow and the same entity/container metadata as ordinary chat attachments.

After upload, Vue validates the returned path with the existing origin-scoped `uploadedFileUrl()` helper. It then sends one ordinary persisted chat message through the existing WebSocket connection. The current composer draft remains untouched.

No audio bytes or Base64 data pass through WebSocket or `chat_message`. No database migration or WebSocket protocol change is required.

## Voice marker and rendering

An uploaded voice recording is represented in message text by an exact, line-oriented internal marker:

```text
VOICE:https://current-origin/path/to/recording.webm
```

`messageParts()` recognizes `VOICE:` only when followed by a valid HTTP(S) URL on that line. It emits a dedicated `voice` part before ordinary URL/media classification. This is required because `.webm` can represent either audio or video.

`ChatMessage.vue` renders `voice` parts with:

```html
<audio controls preload="none"></audio>
```

Ordinary `.webm` URLs remain video. Existing audio extensions continue to render as audio, and existing video extensions continue to render as video. Invalid or malformed voice markers remain escaped ordinary text and never become arbitrary HTML.

Reply quotes show a voice marker as plain quoted text because quote fields are intentionally non-interactive escaped text.

## State and error handling

Recording state is local to the current Vue chat page and is never persisted.

- Permission denial reports a localized microphone-access error.
- Unsupported browser/format reports a localized recording-unavailable error.
- Recorder failure, empty audio, upload failure, invalid upload response, or socket-send failure reports a localized error.
- Every success/failure/unmount/channel-change path clears the five-minute timer and stops every stream track.
- Upload/send failure does not clear or alter the existing composer draft.
- The UI prevents concurrent recording uploads and duplicate sends.

## Testing

Frontend contract tests cover:

- supported-format selection and extension mapping;
- first-click start and second-click stop/send state transitions;
- automatic stop/send at exactly five minutes;
- idempotent stop at the timeout/click boundary;
- Blob-to-File wrapping;
- existing upload metadata and origin-scoped URL validation;
- one ordinary socket message with the `VOICE:` marker;
- unchanged composer draft;
- guest/disconnected/uploading disabled states;
- permission, unsupported-format, empty-recording, upload, and send failures;
- timer and microphone-track cleanup on every terminal path;
- strict voice-marker parsing;
- `<audio controls preload="none">` rendering;
- ordinary WebM links remaining video;
- no regression in existing audio/video/file messages.

The SPA build remains a separate user-run step.
