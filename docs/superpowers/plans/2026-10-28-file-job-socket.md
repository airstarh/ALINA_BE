# File Job Socket Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the durable `alina_socket` backend that processes generic file jobs and publishes authorized progress notifications, beginning with video transcoding.

**Architecture:** A single React event loop hosts the public Ratchet server, a private wake socket, and one asynchronous worker. MySQL owns job state and leases; injectable storage and process interfaces keep protocol and recovery behavior testable without Docker or real FFmpeg.

**Tech Stack:** PHP 8.5, Ratchet, ReactPHP socket/event loop, Illuminate Database, FFmpeg/ffprobe, MySQL 8.4.

**Spec:** `docs/superpowers/specs/2026-10-28-file-job-socket-design.md`

## Global Constraints

- Do not commit unless the user explicitly requests it.
- Do not build either JavaScript SPA.
- Do not alter existing chat `/ws` behavior.
- Do not create a normal `file` row before successful processing.
- Do not execute migrations or restart/reload containers during implementation.
- Preserve existing model hooks; use the `file` model for final persistence.

## Review Focus

- A valid user attempting to subscribe to another owner's job must receive an error and no snapshot.
- A stale worker lease must be recoverable without allowing two simultaneous claims.
- Malformed FFmpeg progress must not decrease progress or exceed 100.
- A failed or killed FFmpeg process must not leave a published file or final MP4.
- A lost private wake notification must still be recovered by startup/safety scanning.

---

### Task 1: Durable storage and authorization contract

**Files:**
- Create: `server/srv/alina/Services/FileJob/FileJobStorage.php`
- Create: `server/srv/alina/Services/FileJob/DatabaseFileJobStorage.php`
- Test: `server/srv/alina/tests/FileJobStorageContractTest.php`

**Interfaces:**
- Produces: job lookup/safe snapshot, ALINA login validation, atomic claim with lease, progress update, completion, retry/failure, and recovery candidate methods.

- [ ] Write a storage-contract test covering owner authorization, atomic claim, expired-lease recovery, monotonic progress, and safe snapshots.
- [ ] Run the test and verify it fails because the storage interface/classes do not exist.
- [ ] Implement the interface and Illuminate-backed storage using the existing `file_job` schema.
- [ ] Run the test and the existing PHP test suite; verify they pass.

### Task 2: Authorized WebSocket notification protocol

**Files:**
- Create: `server/srv/alina/Services/FileJob/FileJobServer.php`
- Create: `server/srv/alina/tests/FileJobProtocolTest.php`
- Reuse: `server/srv/alina/tests/support/ChatTestSupport.php` only where its connection fake is generic.

**Interfaces:**
- Consumes: `FileJobStorage` authorization and snapshot methods.
- Produces: `subscribe`, `status`, and `publishJob(string $publicId)` protocol behavior.

- [ ] Write failing protocol tests for malformed messages, valid subscription, wrong owner/token, status replay, and publication only to authorized subscribers.
- [ ] Run the tests and confirm feature-missing failures.
- [ ] Implement the smallest Ratchet component satisfying the protocol and payload-size limits.
- [ ] Run protocol tests and the existing PHP test suite.

### Task 3: Safe FFmpeg command and progress parser

**Files:**
- Create: `server/srv/alina/Services/FileJob/VideoTranscoder.php`
- Create: `server/srv/alina/Services/FileJob/ProcessResult.php`
- Create: `server/srv/alina/tests/VideoTranscoderTest.php`

**Interfaces:**
- Produces: `probe(string $source)`, `command(string $source, string $output)`, and monotonic progress parsing based on probed duration.

- [ ] Write failing tests proving accepted video probe data, rejected non-video input, exact argument-array safety, optional audio mapping, and bounded monotonic progress.
- [ ] Run tests and confirm feature-missing failures.
- [ ] Implement ffprobe validation and FFmpeg argument generation without shell command concatenation.
- [ ] Implement nonblocking `proc_open` execution integration points and progress parsing.
- [ ] Run tests and the existing PHP test suite.

### Task 4: Single-worker orchestration, leases, and finalization

**Files:**
- Create: `server/srv/alina/Services/FileJob/FileJobWorker.php`
- Create: `server/srv/alina/tests/FileJobWorkerTest.php`

**Interfaces:**
- Consumes: `FileJobStorage`, `VideoTranscoder`, event loop, and `file` model persistence.
- Produces: `wake(string $publicId, string $secret)`, recovery scan, sequential execution, retry/failure cleanup, and `FileJobServer::publishJob()` calls.

- [ ] Write failing tests for valid/invalid wake secrets, one-job concurrency, successful atomic finalization, failed cleanup, two-attempt retry, and expired-lease recovery.
- [ ] Run tests and confirm expected failures.
- [ ] Implement orchestration with injected filesystem/process adapters where external side effects require isolation.
- [ ] Finalize through the existing `file` model with explicit `owner_id`; never bypass its hooks.
- [ ] Run worker tests and the existing PHP test suite.

### Task 5: Runner and private wake socket

**Files:**
- Create: `server/srv/alina/Services/FileJob/FileJobServerRunner.php`
- Create: `server/srv/alina/program/socket/p.socket.run.php`
- Modify: `dc.all.php82.yml`
- Test: `server/srv/alina/tests/FileJobRunnerTest.php`

**Interfaces:**
- Consumes: all prior components.
- Produces: public port `8082`, private line-delimited JSON wake port `8083`, PID cleanup, keepalive, startup recovery, and graceful shutdown cleanup.

- [ ] Write failing tests for private wake framing/size limits and runner configuration.
- [ ] Run tests and confirm expected failures.
- [ ] Implement the runner and private socket parser; add internal port `8083` to Compose without an Nginx route.
- [ ] Run runner tests, all PHP tests, `php -l` on new files, Compose static validation if available, and `git diff --check`.

