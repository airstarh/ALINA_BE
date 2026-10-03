#!/bin/bash
# shellcheck disable=SC1091

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SOCAT_MARKER="$(mktemp)"
rm -- "$SOCAT_MARKER"
trap 'rm -f -- "$SOCAT_MARKER"' EXIT
export SOCAT_MARKER

pkill() {
    return 1
}

docker() {
    printf '%s\n' '172.20.0.2'
}

socat() {
    : > "$SOCAT_MARKER"
}

source "$ROOT_DIR/admin/bin/function/asd.sh"
source "$ROOT_DIR/admin/at/sss/socet.restart.sh"
wait

[[ -f "$SOCAT_MARKER" ]] || {
    echo "FAIL: socat was not started when no previous process existed" >&2
    exit 1
}

echo "PASS: socat restart starts from an already-stopped state"
