# File Job Socket Design

## Scope

This slice builds the reusable ALINA backend service that executes durable file jobs and publishes their state over WebSocket. The first job type is `video_transcode`. Upload-controller and ALINA_FE changes are intentionally a later slice.

## Runtime

- `alina_socket` runs one React event loop.
- Public WebSocket notifications listen on port `8082` through `/ws-file`.
- A private Docker-network wake socket listens on port `8083`; it is never proxied by Nginx.
- MySQL `file_job` is authoritative. A disconnect never cancels work.
- The HTTP backend inserts a job and wakes the service using `public_id` plus a one-time secret whose hash is stored in `payload`.
- Startup and a slow recovery sweep claim queued or expired leased jobs, so a lost wake-up or container crash cannot strand work. This is recovery, not browser polling.
- Initially only one job is processed at a time.

## Authorization

- WebSocket clients subscribe with `publicId`, `uid`, and the existing ALINA login token.
- The service validates the login row and requires `file_job.owner_id` to match.
- The browser never supplies filesystem paths, worker IDs, status, or progress.
- Wake requests are accepted only when the supplied secret matches the hash stored with that queued job.

## Video processing

- Validate the staged source with `ffprobe` before conversion.
- Execute FFmpeg without shell interpolation.
- Output MP4/H.264/AAC, maximum width 1280, 30 fps, CRF 23, preset `veryfast`, `yuv420p`, optional audio, and `+faststart`.
- Parse `-progress pipe:1` output and persist/broadcast integer progress from 0 through 100.
- Write to a temporary output and atomically rename only after FFmpeg succeeds.
- On success create the ordinary `file` model record and mark the job `completed`.
- On interruption remove incomplete output, release or fail the lease, and never expose a partial normal file.
- Retry transient processing failure at most twice; invalid input fails immediately.

## Protocol

Client messages:

- `subscribe`: `{type, publicId, uid, token}`
- `status`: `{type, publicId}` after successful subscription

Server messages:

- `subscribed`: current safe job snapshot
- `job`: updated safe job snapshot
- `error`: stable public message without internal paths or FFmpeg command details

The safe snapshot contains only `publicId`, `type`, `status`, `progress`, timestamps, and a public error message.

