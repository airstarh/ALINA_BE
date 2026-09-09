#!/bin/bash
# shellcheck disable=SC1090

set -euo pipefail

ADMIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ALINA_PROFILE_DIR="${ALINA_PROFILE_DIR:-$ADMIN_DIR/bin/config/host}"
export ALINA_PROFILE_DIR

usage() {
    cat <<'USAGE'
Usage:
  bash admin/run.sh <profile> <script-relative-to-admin> [script arguments]

Examples:
  bash admin/run.sh sss at/sss/docker.up.sh
  bash admin/run.sh bbb do/code/deploy.sh
  bash admin/run.sh sss do/sql/backup.sh zero

Set ALINA_DRY_DISPATCH=1 to validate and print a command without running it.
USAGE

    echo ""
    echo "Profiles:"
    find "$ALINA_PROFILE_DIR" -maxdepth 1 -type f -name '*.sh' -printf '%f\n' \
        | sed 's/\.sh$//' \
        | sort \
        | sed 's/^/  /'
}

fail() {
    echo "Error: $*" >&2
    echo "" >&2
    usage >&2
    exit 2
}

format_arguments() {
    local formatted

    if (( $# == 0 )); then
        printf '%s' '-'
        return
    fi

    printf -v formatted '%q ' "$@"
    printf '%s' "${formatted% }"
}

if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
    usage
    exit 0
fi

(( $# >= 2 )) || fail "profile and script path are required"

PROFILE="$1"
SCRIPT_PATH="$2"
shift 2

PROFILE_FILE="$ALINA_PROFILE_DIR/$PROFILE.sh"
[[ "$PROFILE" != */* && -f "$PROFILE_FILE" ]] || fail "unknown profile: $PROFILE"

[[ "$SCRIPT_PATH" != /* ]] || fail "script path must be relative to admin/"
SCRIPT_FILE="$(realpath -e -- "$ADMIN_DIR/$SCRIPT_PATH")" \
    || fail "script file not found: $SCRIPT_PATH"

case "$SCRIPT_FILE" in
    "$ADMIN_DIR"/*.sh) ;;
    *) fail "script must be a .sh file inside admin/: $SCRIPT_PATH" ;;
esac

source "$ADMIN_DIR/bin/bootstrap.sh"
alina_bootstrap "$PROFILE"

if [[ "${ALINA_DRY_DISPATCH:-0}" == "1" ]]; then
    printf 'profile=%s script=%s arguments=%s\n' \
        "$PROFILE" "$SCRIPT_PATH" "$(format_arguments "$@")"
    exit 0
fi

cd "$ALINA_ROOT"
source "$SCRIPT_FILE" "$@"
