#!/bin/bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

fail() {
    echo "FAIL: $*" >&2
    exit 1
}

validate_profile() {
    local profile="$1"

    bash -c '
        set -euo pipefail

        source "$1/admin/bin/bootstrap.sh"
        alina_bootstrap "$2"

        [[ "$ALINA_PROFILE" == "$2" ]]
        [[ "$ALINA_ADMIN" == "$1/admin" ]]
        [[ "$ALINA_ROOT" == "$1" ]]
        (( ${#ALINA_BASES[@]} > 0 ))
        (( ${#A_LIST_PROJECTS[@]} > 0 ))

        default_found=0
        for project in "${A_LIST_PROJECTS[@]}"; do
            if [[ "$project" == "$ALINA_DEFAULT_PROJECT" ]]; then
                default_found=1
                break
            fi
        done
        (( default_found == 1 ))

        if [[ "$2" != "local" ]]; then
            for variable in \
                ALINA_REMOTE_HOST \
                ALINA_REMOTE_USER \
                ALINA_REMOTE_URL \
                ALINA_REMOTE_SSH
            do
                [[ -n "${!variable:-}" ]]
            done
        fi
    ' bash "$ROOT_DIR" "$profile"
}

for profile in sss bbb local; do
    validate_profile "$profile" || fail "$profile profile contract is invalid"
done

if validate_profile unknown >/dev/null 2>&1; then
    fail "unknown profile was accepted"
fi

echo "PASS: configuration profile contracts"
