#!/bin/bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

fail() {
    echo "FAIL: $*" >&2
    exit 1
}

profile_snapshot() {
    local profile="$1"

    bash -c '
        source "$1/admin/bin/bootstrap.sh"
        alina_bootstrap "$2"
        IFS=" "
        printf "%s|%s|%s|%s|%s\n" \
            "$ALINA_PROFILE" \
            "${ALINA_REMOTE_HOST:-}" \
            "${ALINA_BASES[*]}" \
            "${A_LIST_PROJECTS[*]}" \
            "$ALINA_DEFAULT_PROJECT"
    ' bash "$ROOT_DIR" "$profile"
}

[[ "$(profile_snapshot sss)" == "sss|ospl1942.ru|zero vov|zero.home vov|zero.home" ]] \
    || fail "sss profile values are incorrect"

[[ "$(profile_snapshot bbb)" == "bbb|bbb|borg|borg.home|borg.home" ]] \
    || fail "bbb profile values are incorrect"

[[ "$(profile_snapshot local)" == "local||zero vov|zero.home vov|zero.home" ]] \
    || fail "local profile values are incorrect"

if profile_snapshot unknown >/dev/null 2>&1; then
    fail "unknown profile was accepted"
fi

bash -c '
    set -euo pipefail
    source "$1/admin/bin/config/sss.sh"
    [[ "$ALINA_REMOTE_HOST" == "ospl1942.ru" ]]
' bash "$ROOT_DIR" || fail "legacy sss config cannot be sourced directly"

echo "PASS: configuration profiles"
