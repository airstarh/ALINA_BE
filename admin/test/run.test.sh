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
grep -q '^  future$' <<< "$FUTURE_HELP" \
    || fail "new profile was not discovered in help"

[[ "$(ALINA_PROFILE_DIR="$TEMP_PROFILE_DIR" dry_run future code deploy)" == \
    "profile=future action=code/deploy target=-" ]] \
    || fail "new profile required a dispatcher code change"

HELP_OUTPUT="$(bash "$RUN_SCRIPT" --help)"
grep -q "Usage:" <<< "$HELP_OUTPUT" || fail "help text is missing"

if dry_run unknown code compile >/dev/null 2>&1; then
    fail "unknown profile was accepted"
fi

if dry_run sss sql backup >/dev/null 2>&1; then
    fail "SQL backup without a database was accepted"
fi

[[ "$(dry_run sss code deploy)" == "profile=sss action=code/deploy target=-" ]] \
    || fail "sss deploy did not resolve"

[[ "$(dry_run bbb sql backup borg)" == "profile=bbb action=sql/backup target=borg" ]] \
    || fail "bbb SQL backup did not resolve"

[[ "$(dry_run local sql restore zero)" == "profile=local action=sql/restore target=zero" ]] \
    || fail "local SQL restore did not resolve"

if dry_run sss sql backup borg >/dev/null 2>&1; then
    fail "database outside the selected profile was accepted"
fi

if dry_run bbb docker up >/dev/null 2>&1; then
    fail "unsupported bbb Docker command was accepted"
fi

if dry_run sss docker config >/dev/null 2>&1; then
    fail "missing sss Docker config action was accepted"
fi

if dry_run sss sql backup zero extra >/dev/null 2>&1; then
    fail "extra command arguments were accepted"
fi

[[ "$(cd /tmp && ALINA_DRY_DISPATCH=1 bash "$RUN_SCRIPT" bbb code deploy)" == \
    "profile=bbb action=code/deploy target=-" ]] \
    || fail "dispatcher depends on the current directory"

echo "PASS: command dispatcher"
