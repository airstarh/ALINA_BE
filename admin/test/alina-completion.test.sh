#!/bin/bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

fail() {
    echo "FAIL: $*" >&2
    exit 1
}

source "$ROOT_DIR/alina"

[[ "$(type -t _alina_complete)" == "function" ]] \
    || fail "sourcing alina did not initialize completion"

COMP_WORDS=(./alina s)
COMP_CWORD=1
_alina_complete
[[ " ${COMPREPLY[*]} " == *" sss "* ]] \
    || fail "profile completion did not suggest sss"

COMP_WORDS=(./alina sss at/sss/docker.r)
COMP_CWORD=2
_alina_complete
[[ " ${COMPREPLY[*]} " == *" at/sss/docker.restart.sh "* ]] \
    || fail "script completion did not suggest the admin-relative path"

echo "PASS: alina shell completion"
