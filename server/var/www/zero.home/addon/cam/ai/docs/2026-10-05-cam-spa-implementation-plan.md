# Balcon Camera Configuration SPA Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use
> `superpowers:subagent-driven-development` (recommended) or
> `superpowers:executing-plans` to implement this plan task-by-task. Steps use
> checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a self-contained, build-free, read-only SPA that selects Balcon
by name and explores its DVRIP configuration through a native PHP backend.

**Architecture:** Native browser JavaScript calls a single PHP JSON dispatcher.
PHP resolves the selected camera from an ignored configuration file, connects
to TCP 34567 through a minimal read-only DVRIP client, and returns sanitized
configuration data. The implementation is isolated under `addon/cam` and does
not use the ALINA API.

**Tech Stack:** PHP 8.2-compatible code, PHP streams, native JavaScript ES
modules, HTML5, CSS, and a dependency-free PHP test runner.

**Spec:**
`server/var/www/zero.home/addon/cam/ai/docs/2026-10-05-cam-spa-design.md`

## Global Constraints

- Keep product code under `server/var/www/zero.home/addon/cam`; keep private
  camera configuration in ignored `_GITOUT/addon/cam/config.php`.
- Support only Balcon in version one, while keeping the camera registry format
  extensible.
- Never return credentials or credential-bearing URLs to JavaScript.
- Implement only DVRIP login and getters; do not implement setters or generic
  arbitrary command dispatch.
- Do not add Composer, npm, Python, system-wide, or ALINA API dependencies.
- Use native JavaScript and CSS; no JavaScript build command is required.
- Do not create Git commits unless the user explicitly requests them. Replace
  commit steps with reviewable working-tree checkpoints.
- Preserve unrelated working-tree changes.

## Review Focus

- Fragmented TCP packets must be read completely without hanging or truncating
  the JSON payload; Task 2 tests partial header/body reads.
- A forged camera or section ID must never select a file or become a raw DVRIP
  command; Tasks 1, 4, and 5 test exact allow-lists.
- Passwords, tokens, and credential-bearing URIs embedded anywhere in nested
  camera responses must be redacted before JSON emission; Task 4 tests
  case-insensitive recursive sanitization without hiding ordinary addresses.
- One unsupported or timed-out section in an all-section request must not erase
  successful sections; Task 4 tests per-section result isolation.
- Double-clicking Connect or Refresh must not let stale responses overwrite the
  current selection; Task 6 tests request cancellation and control state.

---

## File map

| File | Responsibility |
|---|---|
| `_GITOUT/addon/cam/config.php` | Private Balcon registry and DVRIP credentials |
| `server/var/www/zero.home/addon/cam/index.php` | Static SPA document shell |
| `server/var/www/zero.home/addon/cam/stuff/php/Config.php` | Load and validate private configuration |
| `server/var/www/zero.home/addon/cam/stuff/php/DvripException.php` | Typed protocol/transport failures |
| `server/var/www/zero.home/addon/cam/stuff/php/DvripTransport.php` | Abstract byte transport for real sockets and tests |
| `server/var/www/zero.home/addon/cam/stuff/php/StreamDvripTransport.php` | Bounded TCP stream I/O |
| `server/var/www/zero.home/addon/cam/stuff/php/DvripClient.php` | DVRIP framing, login, read-only getters, logout/cleanup |
| `server/var/www/zero.home/addon/cam/stuff/php/CameraService.php` | Safe section mapping, normalization, and sanitization |
| `server/var/www/zero.home/addon/cam/stuff/php/api.php` | HTTP parameter validation and JSON response envelope |
| `server/var/www/zero.home/addon/cam/stuff/js/api.js` | Browser API client and cancellation |
| `server/var/www/zero.home/addon/cam/stuff/js/tree.js` | Safe recursive configuration renderer |
| `server/var/www/zero.home/addon/cam/stuff/js/app.js` | SPA state and interaction controller |
| `server/var/www/zero.home/addon/cam/stuff/css/app.css` | Responsive dashboard styling |
| `server/var/www/zero.home/addon/cam/stuff/tests/*.test.php` | Dependency-free PHP unit/integration tests |
| `server/var/www/zero.home/addon/cam/stuff/tests/run.php` | Test discovery and assertion runner |
| `server/var/www/zero.home/addon/cam/stuff/tests/fixtures/` | Sanitized DVRIP packets and JSON payloads |

### Task 1: Private configuration loader

**Files:**

- Modify: `_GITOUT/addon/cam/config.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/Config.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/run.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/Config.test.php`

**Interfaces:**

- Produces: `Cam\Config::load(string $path): self`
- Produces: `Cam\Config::publicCameras(): array`
- Produces: `Cam\Config::defaultCameraId(): string`
- Produces: `Cam\Config::camera(string $id): array`
- Camera private shape: `name`, `host`, and `dvrip` containing `port`,
  `username`, `password`, and optional `timeout`.

- [ ] **Step 1: Add the dependency-free test runner**

Create `run.php` with test registration, strict equality, exception, and
pass/fail helpers. It accepts optional test-file arguments and exits non-zero
on any failure.

- [ ] **Step 2: Write failing configuration tests**

Add tests named `config_returns_only_public_camera_metadata`,
`config_selects_balcon_by_default`, `config_rejects_unknown_camera`,
`config_rejects_missing_required_dvrip_keys`, and
`config_does_not_expose_private_values`. Use temporary PHP fixtures; assert the
public result is exactly `id` and `name` while `camera('balcon')` provides the
private connection array only to PHP.

- [ ] **Step 3: Verify the tests fail**

Run:

```bash
php server/var/www/zero.home/addon/cam/stuff/tests/run.php Config.test.php
```

Expected: non-zero exit because `Cam\Config` does not exist.

- [ ] **Step 4: Implement `Config.php`**

Load one explicit path, require an array with a `default` camera ID and a
`cameras` map, validate camera IDs with `^[a-z][a-z0-9_-]*$`, validate host and
port types/ranges, and return only `id`/`name` from `publicCameras()`.

- [ ] **Step 5: Normalize the ignored Balcon configuration**

Change the private file to the validated `default` plus `cameras.balcon`
structure and populate its DVRIP values from the confirmed private inventory.
Do not add Door or another camera.

- [ ] **Step 6: Verify Task 1**

Run the Task 1 test command and `php -l` on `Config.php` and private
`config.php`. Expected: all tests pass and both syntax checks report no errors.

- [ ] **Step 7: Working-tree checkpoint**

Review only Task 1 files and confirm `_GITOUT/addon/cam/config.php` remains
ignored. Do not commit.

### Task 2: DVRIP framing and bounded transport

**Files:**

- Create: `server/var/www/zero.home/addon/cam/stuff/php/DvripException.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/DvripTransport.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/StreamDvripTransport.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/php/DvripClient.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/DvripFraming.test.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/fixtures/login-ok.bin`

**Interfaces:**

- Produces: `Cam\DvripTransport::connect(): void`, `writeAll(string $bytes):
  void`, `readExact(int $length): string`, and `close(): void`.
- Produces: `Cam\StreamDvripTransport::__construct(string $host, int $port,
  float $timeoutSeconds = 5.0)`.
- Produces: `Cam\DvripClient::__construct(DvripTransport $transport, int
  $maxPayloadBytes = 2097152)`.
- Produces internal framing methods exercised through a test transport:
  little-endian 20-byte header, `0xff` marker, version `0`, JSON followed by
  `\n\0`, and bounded payload length.

- [ ] **Step 1: Write failing framing tests**

Test exact header fields and payload terminator, a response split across many
short reads, EOF during the header, EOF during the body, invalid marker,
payloads over 2 MiB, malformed JSON, and socket timeout mapping to stable
`DvripException` codes.

- [ ] **Step 2: Verify framing tests fail**

Run `php .../stuff/tests/run.php DvripFraming.test.php`.
Expected: non-zero exit because the transport and client classes do not exist.

- [ ] **Step 3: Implement transport contracts and exceptions**

Define the interface and a `DvripException` carrying a stable public error code
separate from its internal diagnostic message.

- [ ] **Step 4: Implement bounded stream transport**

Use `stream_socket_client`, stream timeouts, a write loop, and an exact-read
loop. Treat zero-length reads, EOF, and timeout as distinct exceptions and
always close the stream deterministically.

- [ ] **Step 5: Implement framing in `DvripClient.php`**

Use PHP little-endian packing/unpacking equivalents for
`BB2xII2xHI`. Increment the sequence after each request, validate marker and
length before reading the body, remove only the terminal `\n\0`, and decode
JSON with exceptions.

- [ ] **Step 6: Verify Task 2**

Run the framing tests and `php -l` on all four PHP classes. Expected: all pass.

- [ ] **Step 7: Working-tree checkpoint**

Review the packet fixture for credentials and confirm it is sanitized. Do not
commit.

### Task 3: DVRIP login and read-only getters

**Files:**

- Modify: `server/var/www/zero.home/addon/cam/stuff/php/DvripClient.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/DvripClient.test.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/fixtures/get-config-ok.bin`

**Interfaces:**

- Produces: `DvripClient::login(string $username, string $password): array`
- Produces: `DvripClient::getConfig(string $command): mixed`
- Produces: `DvripClient::getSystemInfo(): array`
- Produces: `DvripClient::getSystemFunction(): array`
- Produces: `DvripClient::close(): void`
- Consumes: the Task 2 transport and framing contracts.

- [ ] **Step 1: Write failing client tests**

Test the Sofia password hash with known non-secret vectors including the empty
password, login message ID `1000`, session parsing, generic getter message ID
`1042`, SystemInfo ID `1020`, SystemFunction ID `1360`, return codes `100`,
`106`, `605`, and `607`, command-name matching, and cleanup after errors.

- [ ] **Step 2: Verify client tests fail**

Run `php .../stuff/tests/run.php DvripClient.test.php`.
Expected: failures for missing login/getter methods.

- [ ] **Step 3: Implement authentication**

Hash with the Sofia MD5-pair/base62 algorithm and send `EncryptType=MD5`,
`LoginType=DVRIP-Web`, `UserName`, and `PassWord`. Store only the returned
numeric session and do not log inputs or packets.

- [ ] **Step 4: Implement the fixed getter surface**

Expose only `getConfig`, `getSystemInfo`, and `getSystemFunction`; keep the raw
request method private. Convert known device return codes into typed exceptions
without echoing camera payloads.

- [ ] **Step 5: Verify Task 3**

Run Tasks 2 and 3 tests plus `php -l DvripClient.php`. Expected: all pass.

- [ ] **Step 6: Working-tree checkpoint**

Confirm public methods cannot send setters, operational IDs, or caller-chosen
numeric message IDs. Do not commit.

### Task 4: Camera service, section isolation, and sanitization

**Files:**

- Create: `server/var/www/zero.home/addon/cam/stuff/php/CameraService.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/CameraService.test.php`

**Interfaces:**

- Produces: `CameraService::sections(): array`
- Produces: `CameraService::status(): array`
- Produces: `CameraService::config(string $section): array`
- Produces: `CameraService::all(): array`
- Consumes: a logged-in `DvripClient` and the exact section map from the spec.

- [ ] **Step 1: Write failing service tests**

Assert the exact 14 section IDs/getters from the spec, rejection of arbitrary
section strings, sanitized status output, recursive redaction of keys matching
`password`, `passwd`, `pwd`, `token`, `secret`, and credential/key fields
case-insensitively; credential removal from URIs; preservation of usernames,
ordinary IP/server/email addresses, and non-sensitive scalar types. Test that
`all()` returns independent
`ok/data/error` results when one getter returns 607 or times out.

- [ ] **Step 2: Verify service tests fail**

Run `php .../stuff/tests/run.php CameraService.test.php`.
Expected: non-zero exit because the service does not exist.

- [ ] **Step 3: Implement section mapping and service methods**

Keep the UI-to-DVRIP mapping as a private constant. `status()` combines
SystemInfo with safe connection metadata; `config()` maps only known IDs;
`all()` catches each section error independently.

- [ ] **Step 4: Implement recursive response sanitization**

Replace sensitive keyed values with `[redacted]`, preserve list shape, and
avoid value-based redaction so ordinary strings remain inspectable. Apply the
sanitizer to every camera-derived API result, including error context.

- [ ] **Step 5: Verify Task 4**

Run Tasks 1–4 tests. Expected: all pass.

- [ ] **Step 6: Working-tree checkpoint**

Review the section map against the approved spec and confirm unsupported raw
commands cannot cross the service boundary. Do not commit.

### Task 5: JSON HTTP API

**Files:**

- Create: `server/var/www/zero.home/addon/cam/stuff/php/api.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/Api.test.php`

**Interfaces:**

- Produces HTTP GET actions: `cameras`, `status`, `sections`, and `config`.
- Produces envelope: `{ok: bool, data: mixed, error: null|{code, message}}`.
- Consumes: `Config`, `StreamDvripTransport`, `DvripClient`, and
  `CameraService`.

- [ ] **Step 1: Write failing API tests**

Run the dispatcher in a test harness with injected service/config factories.
Assert the exact public camera list, required `camera` and `section`
parameters, unknown-action/camera/section rejection, `section=all` behavior,
HTTP status mapping, JSON content type, no-cache headers, and absence of fixture
credentials in encoded responses.

- [ ] **Step 2: Verify API tests fail**

Run `php .../stuff/tests/run.php Api.test.php`.
Expected: non-zero exit because the dispatcher does not exist.

- [ ] **Step 3: Implement dispatcher and response helpers**

Separate request handling from the executable edge so tests call a pure
`handle(array $query): array` path. Resolve the private config using
`dirname(__DIR__, 8) . '/_GITOUT/addon/cam/config.php'` from `stuff/php`, and
emit only the approved envelope.

- [ ] **Step 4: Implement service lifecycle**

For camera actions, create the transport/client, login using private config,
invoke one service operation, and close in `finally`. Do not connect for the
`cameras` action.

- [ ] **Step 5: Verify Task 5**

Run all PHP tests and syntax-check every PHP file under `addon/cam`. Expected:
all pass with no warnings or output before JSON.

- [ ] **Step 6: Working-tree checkpoint**

Search product code and test fixtures for the real password and
credential-bearing URLs. Confirm no matches outside ignored private files. Do
not commit.

### Task 6: Native JavaScript SPA and configuration tree

**Files:**

- Delete: `server/var/www/zero.home/addon/cam/index.html`
- Create: `server/var/www/zero.home/addon/cam/index.php`
- Create: `server/var/www/zero.home/addon/cam/stuff/js/api.js`
- Create: `server/var/www/zero.home/addon/cam/stuff/js/tree.js`
- Create: `server/var/www/zero.home/addon/cam/stuff/js/app.js`
- Create: `server/var/www/zero.home/addon/cam/stuff/css/app.css`
- Create: `server/var/www/zero.home/addon/cam/stuff/tests/ui.test.mjs`

**Interfaces:**

- Produces: `CameraApi.listCameras()`, `status(cameraId)`,
  `sections(cameraId)`, `config(cameraId, section)`, and `abortActive()`.
- Produces: `renderTree(value, document): HTMLElement` using text nodes only.
- Consumes: the Task 5 JSON API.

- [ ] **Step 1: Write failing JavaScript behavior tests**

Using Node's built-in test runner and minimal DOM fixtures, test camera-list
normalization, Balcon default selection, URL parameter encoding, response
envelope errors, aborting a superseded request, recursive tree rendering with
text rather than HTML, and stale-response rejection after a second Connect.

- [ ] **Step 2: Verify UI tests fail**

Run:

```bash
node --test server/var/www/zero.home/addon/cam/stuff/tests/ui.test.mjs
```

Expected: non-zero exit because the browser modules do not exist.

- [ ] **Step 3: Implement `api.js` and `tree.js`**

Use `fetch`, `URLSearchParams`, and `AbortController`. Render dictionaries as
expandable `details` elements, lists with indexes, and scalars with typed CSS
classes. Insert all camera values through `textContent`/text nodes.

- [ ] **Step 4: Implement the document shell and SPA controller**

Create an accessible selector, Connect button, status region, section
navigation, Refresh controls, loading state, errors, and configuration content.
Load sections only after Connect and on demand. Disable duplicate controls
during active requests and ignore results belonging to an older selection.

- [ ] **Step 5: Implement responsive styling**

Use a two-column dashboard on wide displays and a single column on narrow
screens. Provide readable nested indentation, scalar type cues, visible focus,
and restrained status/error colors without external assets.

- [ ] **Step 6: Verify Task 6**

Run the Node tests, PHP tests, PHP syntax checks, and inspect the SPA in a
browser at desktop and narrow widths. Expected: Balcon is selected on load;
Connect is the only initial action; no credential field exists; navigation and
tree expansion work.

- [ ] **Step 7: Working-tree checkpoint**

Confirm `index.html` is gone, `index.php` is the sole entry point, no generated
build artifacts exist, and no JavaScript contains private values. Do not
commit.

### Task 7: Read-only live verification and documentation

**Files:**

- Modify:
  `server/var/www/zero.home/addon/cam/ai/docs/balcon-vendor-config.md`
- Modify:
  `server/var/www/zero.home/addon/cam/ai/docs/2026-10-05-cam-spa-design.md`
- Create:
  `server/var/www/zero.home/addon/cam/ai/docs/cam-spa-verification.md`

**Interfaces:**

- Consumes: complete SPA and read-only API.
- Produces: reproducible verification notes without credentials.

- [ ] **Step 1: Start a local PHP development server for this app only**

Use a bounded local listener and document its PID so it can be stopped after
verification. This is a test server, not a deployment action.

- [ ] **Step 2: Verify public camera discovery**

Request `action=cameras`; assert only Balcon's ID/name and default ID appear,
with no host, port, username, password, or URI.

- [ ] **Step 3: Verify live read-only endpoints**

Request status and each allow-listed section. Record success, unsupported, and
timeout outcomes by section without recording configuration values. Confirm no
setter or operational message is emitted.

- [ ] **Step 4: Verify the browser flow**

Open the SPA, confirm Balcon is preselected, press Connect, navigate every
section, refresh one section, exercise an induced connection error using a
temporary test fixture, and confirm recovery without reloading the page.

- [ ] **Step 5: Stop the local test server**

Confirm its listener is closed.

- [ ] **Step 6: Update documentation**

Record the final file layout, verified PHP/browser versions, endpoint behavior,
live section results, known limitations, and exact non-mutating verification
commands. Do not record camera values or credentials.

- [ ] **Step 7: Run final verification**

Run all PHP and Node tests, syntax-check PHP, run `git diff --check`, inspect
`git status --short`, confirm `_GITOUT/addon/cam/config.php` is ignored, and
search tracked cam files for known credential patterns. Expected: all checks
pass and only intended cam files are changed.

- [ ] **Step 8: Final working-tree review**

Review the complete diff against the approved spec. Do not commit unless the
user separately requests a commit.
