# PHP 8.5 compatibility progress

Updated: 2026-10-23

## User constraints

- Never edit anything under `vendor/` or `node_modules/`.
- Do not create Git commits unless explicitly requested.
- Do not build the JavaScript SPA without asking immediately before the build.
- User works through a terminal and needs literal paths and concise, precise instructions.
- Current work is about PHP 8.5 compatibility and text-chat testing.

## Completed ALINA-owned code changes

All paths below are relative to the ALINA_BE repository root:

- `server/srv/alina/AppExceptionCatcher.php`
  - Captures the original exception before framework-dependent error handling.
  - Protects response translation, transaction rollback, error processing, redirects, and exception-page rendering in separate guarded stages.
  - Prevents recursive exception handling from replacing the original error.
  - Keeps the normal exception page path when framework services work; falls back to plain HTTP 500 when they do not.
- `server/srv/alina/mvc/Model/_BaseAlinaModel.php`
  - Changed implicit nullable parameters to `?string` / `?int`.
- `server/srv/alina/Utils/db/mysql/DbManager.php`
  - Changed `stdClass $creds = null` to `?stdClass $creds = null`.
- `server/srv/alina/Utils/Data.php`
  - Replaced deprecated `FILTER_SANITIZE_STRING` with `strip_tags()` and explicit quote encoding. Regression test confirmed old output for the representative input is preserved.
- `server/srv/alina/Utils/HttpRequest.php`
  - Removed obsolete `curl_close()` and the old resource-only close logic; modern `CurlHandle` references are released by resetting the property.
- `server/srv/alina/Services/Chat/ChatServer.php`
  - Replaced deprecated PHP 8.5 `SplObjectStorage::attach/contains/detach` calls with `offsetSet/offsetExists/offsetUnset`.
- Added tests:
  - `server/srv/alina/tests/AppExceptionCatcherBootstrapSafetyTest.php`
  - `server/srv/alina/tests/AppExceptionCatcherNormalFlowTest.php`
  - `server/srv/alina/tests/DataProperNameCompatibilityTest.php`

There was a brief incorrect edit to `ChatHistoryReconnectTest.php` changing React `Timers::contains()` to `offsetExists()`. Test run exposed that this is a different class; those two lines were restored. The test file should have no net change from this work.

## Verification already performed

- PHP 8.5.12 cold compilation of `_BaseAlinaModel.php` and `DbManager.php` with `E_DEPRECATED` promoted to exceptions passed after nullable parameter corrections.
- PHP syntax checks for all ALINA-owned PHP files passed inside container `alina_php82` (despite service name, it runs PHP 8.5.12).
- All directly runnable backend tests passed, excluding the legacy `Alina001Test.php` and `VanyaControllerTest.php` from host execution because `AppBoot.php` expects `/srv/alina/App.php` inside a container. User later said Vanya-related things were removed; verify current tree before referring to that test.
- All `Chat*Test.php` scripts passed after chat collection changes.
- Exception catcher regression tests passed locally and in container.
- `git diff --check` passed at the time.
- No vendor or node_modules files edited; no JavaScript build; no commit.
- Chat daemon was not restarted because the user was actively testing. To load current ChatServer code when convenient:
  `docker restart alina_php82chat`

## Composer dependency audit (inside `alina_php82chat`)

`composer outdated --direct --format=json` reported:

- `ezyang/htmlpurifier` installed 4.19.0; latest 4.19.1 (safe patch)
- `lcobucci/jwt` installed 5.6.0; latest 5.6.1 (safe patch)
- `guzzlehttp/guzzle` installed 7.13.1; latest 8.3.0 (major)
- `illuminate/database` and `illuminate/events` installed 12.62.0; latest 13.38.0 (major)
- `intervention/image` installed 3.11.8; latest 4.4.0 (major)
- `phpunit/phpunit` installed 9.6.34; latest 13.4.3 (major)

No Composer dependencies have been updated.

## Remaining known PHP 8.5 deprecations

Runtime log showed seven PHP 8.5 deprecations from old Ratchet dependencies in `vendor/`:

- `cboden/ratchet` v0.4.4
- `ratchet/rfc6455` v0.3.1

Examples: implicit nullable parameters and non-canonical `(boolean)` / `(double)` casts. These files must not be edited directly. `cboden/ratchet` v0.4.4 was shown by Composer as installed/latest in that environment. If addressing these, explore a maintained fork, Composer-managed patch, or replacement library; discuss before changing dependency architecture.

Other audits found no remaining ALINA-owned uses of argumentless `get_class()` / `get_parent_class()`, `${var}` interpolation, `utf8_encode/decode`, old CSV optional signatures, or logged dynamic-property deprecations. `FILTER_SANITIZE_STRING` and deprecated PHP `SplObjectStorage` usages were corrected.

## Suggested continuation

1. Check `git status` and current files first because the user may have made additional changes.
2. If user asks to update libraries, start with the safe patch upgrades (`htmlpurifier`, `lcobucci/jwt`) separately from major upgrades; do not touch vendor files directly.
3. Treat Ratchet cleanup as separate architectural/dependency work, preserving the user's no-vendor-edit rule.
