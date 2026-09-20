#!/bin/bash
# shellcheck disable=SC1090

set -euo pipefail

(( $# >= 4 )) || {
    echo "Error: admin directory, profile directory, profile, and script are required" >&2
    exit 2
}

ADMIN_DIR="$(realpath -e -- "$1")"
ALINA_PROFILE_DIR="$(realpath -e -- "$2")"
PROFILE="$3"
SCRIPT_PATH="$4"
shift 4

export ALINA_PROFILE_DIR

if [[ "$SCRIPT_PATH" == /* ]]; then
    SCRIPT_FILE="$(realpath -e -- "$SCRIPT_PATH")"
else
    SCRIPT_FILE="$(realpath -e -- "$ADMIN_DIR/$SCRIPT_PATH")"
fi

case "$SCRIPT_FILE" in
    "$ADMIN_DIR"/*.sh) ;;
    *)
        echo "Error: privileged script must be a .sh file inside admin/: $SCRIPT_PATH" >&2
        exit 2
        ;;
esac

source "$ADMIN_DIR/bin/bootstrap.sh"
alina_bootstrap "$PROFILE"

cd "$ALINA_ROOT"
source "$SCRIPT_FILE" "$@"
