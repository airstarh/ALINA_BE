#!/bin/bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
RUN_SCRIPT="$ROOT_DIR/admin/run.sh"

fail() {
    echo "FAIL: $*" >&2
    exit 1
}

dry_run() {
    ALINA_DRY_DISPATCH=1 bash "$RUN_SCRIPT" "$@"
}

TEMP_PROFILE_DIR="$(mktemp -d)"
MOCK_BIN="$(mktemp -d)"
trap 'rm -r -- "$TEMP_PROFILE_DIR" "$MOCK_BIN"' EXIT
cp "$ROOT_DIR/admin/bin/config/host/bbb.sh" "$TEMP_PROFILE_DIR/future.sh"

printf '#!/bin/bash\nexec env "$@"\n' > "$MOCK_BIN/sudo"
chmod +x "$MOCK_BIN/sudo"

FUTURE_HELP="$(ALINA_PROFILE_DIR="$TEMP_PROFILE_DIR" bash "$RUN_SCRIPT" --help)"
grep -q '^  future$' <<< "$FUTURE_HELP" || fail "new profile was not discovered"

if dry_run unknown do/code/deploy.sh >/dev/null 2>&1; then
    fail "unknown profile was accepted"
fi

if dry_run sss >/dev/null 2>&1; then
    fail "missing script path was accepted"
fi

if dry_run sss does/not/exist.sh >/dev/null 2>&1; then
    fail "missing script file was accepted"
fi

[[ "$(dry_run sss do/code/deploy.sh)" == \
    "profile=sss script=do/code/deploy.sh arguments=-" ]] \
    || fail "sss deploy path did not resolve"

[[ "$(dry_run bbb do/sql/backup.sh borg)" == \
    "profile=bbb script=do/sql/backup.sh arguments=borg" ]] \
    || fail "SQL backup argument did not resolve"

[[ "$(cd /tmp && ALINA_DRY_DISPATCH=1 bash "$RUN_SCRIPT" bbb do/code/deploy.sh)" == \
    "profile=bbb script=do/code/deploy.sh arguments=-" ]] \
    || fail "runner depends on the current directory"

[[ "$(ALINA_PROFILE_DIR="$TEMP_PROFILE_DIR" bash "$RUN_SCRIPT" future test/support/capture.sh XXX YYY N)" == \
    "future|XXX|YYY|N" ]] \
    || fail "profile or script arguments were not passed to the action"

[[ "$(HOME=/tmp/alina-home PATH="$MOCK_BIN:$PATH" bash "$RUN_SCRIPT" sss test/support/alina-sudo.capture.sh XXX YYY)" == \
    "sss|remote-set|arrays-set|function|/tmp/alina-home|XXX|YYY" ]] \
    || fail "alina_sudo did not recreate the current admin context"

echo "PASS: profile-aware script runner"
