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
trap 'rm -r -- "$TEMP_PROFILE_DIR"' EXIT
cp "$ROOT_DIR/admin/bin/config/host/bbb.sh" "$TEMP_PROFILE_DIR/future.sh"

FUTURE_HELP="$(ALINA_PROFILE_DIR="$TEMP_PROFILE_DIR" bash "$RUN_SCRIPT" --help)"
grep -q '^  future$' <<< "$FUTURE_HELP" || fail "new profile was not discovered"

if dry_run unknown bin/script/code/deploy.sh >/dev/null 2>&1; then
    fail "unknown profile was accepted"
fi

if dry_run sss >/dev/null 2>&1; then
    fail "missing script path was accepted"
fi

if dry_run sss does/not/exist.sh >/dev/null 2>&1; then
    fail "missing script file was accepted"
fi

[[ "$(dry_run sss bin/script/code/deploy.sh)" == \
    "profile=sss script=bin/script/code/deploy.sh arguments=-" ]] \
    || fail "sss deploy path did not resolve"

[[ "$(dry_run bbb bin/script/sql/backup.sh borg)" == \
    "profile=bbb script=bin/script/sql/backup.sh arguments=borg" ]] \
    || fail "SQL backup argument did not resolve"

[[ "$(cd /tmp && ALINA_DRY_DISPATCH=1 bash "$RUN_SCRIPT" bbb bin/script/code/deploy.sh)" == \
    "profile=bbb script=bin/script/code/deploy.sh arguments=-" ]] \
    || fail "runner depends on the current directory"

[[ "$(ALINA_PROFILE_DIR="$TEMP_PROFILE_DIR" bash "$RUN_SCRIPT" future test/support/capture.sh XXX YYY N)" == \
    "future|bbb|XXX|YYY|N" ]] \
    || fail "profile or script arguments were not passed to the action"

echo "PASS: profile-aware script runner"
