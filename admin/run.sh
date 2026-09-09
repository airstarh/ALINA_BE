#!/bin/bash
# shellcheck disable=SC2034,SC1090

set -euo pipefail

ADMIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$ADMIN_DIR/.." && pwd)"
ALINA_PROFILE_DIR="${ALINA_PROFILE_DIR:-$ADMIN_DIR/bin/config/host}"
export ALINA_PROFILE_DIR

usage() {
    cat <<'USAGE'
Usage:
  bash admin/run.sh <profile> code <compile|deploy>
  bash admin/run.sh <profile> sql <backup|restore> <database>
  bash admin/run.sh <profile> sql <download|migrate>
  bash admin/run.sh <profile> dyn <backup|restore>
  bash admin/run.sh local docker <build|config|up|down|restart>
  bash admin/run.sh sss docker <build|up|down|restart>

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

contains_database() {
    local requested="$1"
    local configured

    for configured in "${ALINA_BASES[@]}"; do
        [[ "$configured" == "$requested" ]] && return 0
    done

    return 1
}

if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
    usage
    exit 0
fi

(( $# >= 3 )) || fail "profile, area, and action are required"
(( $# <= 4 )) || fail "too many command arguments"

PROFILE="$1"
AREA="$2"
ACTION="$3"
TARGET="${4:-}"

PROFILE_FILE="$ALINA_PROFILE_DIR/$PROFILE.sh"
[[ "$PROFILE" != */* && -f "$PROFILE_FILE" ]] || fail "unknown profile: $PROFILE"

case "$AREA/$ACTION" in
    code/compile)
        [[ -z "$TARGET" ]] || fail "code compile does not accept a target"
        ACTION_FILE="bin/script/code/compile.sh"
        ;;
    code/deploy)
        [[ "$PROFILE" != "local" ]] || fail "code deploy requires a remote profile"
        [[ -z "$TARGET" ]] || fail "code deploy does not accept a target"
        ACTION_FILE="bin/script/code/deploy.sh"
        ;;
    sql/backup|sql/restore)
        [[ -n "$TARGET" ]] || fail "$AREA $ACTION requires a database"
        ACTION_FILE="bin/script/sql/$ACTION.sh"
        ;;
    sql/download)
        [[ "$PROFILE" != "local" ]] || fail "SQL download requires a remote profile"
        [[ -z "$TARGET" ]] || fail "SQL download does not accept a target"
        ACTION_FILE="bin/script/sql/dwl.sh"
        ;;
    sql/migrate)
        [[ -z "$TARGET" ]] || fail "SQL migrate does not accept a target"
        ACTION_FILE="bin/script/sql/migrate.sh"
        ;;
    dyn/backup|dyn/restore)
        [[ "$PROFILE" != "local" ]] || fail "dynamic-file transfer requires a remote profile"
        [[ -z "$TARGET" ]] || fail "dynamic-file transfer does not accept a target"
        ACTION_FILE="bin/script/dyn/$ACTION.sh"
        ;;
    docker/config)
        [[ "$PROFILE" == "local" ]] || fail "Docker config is only configured for local"
        [[ -z "$TARGET" ]] || fail "Docker commands do not accept a target"
        ACTION_FILE="at/local/docker.config.sh"
        ;;
    docker/build|docker/up|docker/down|docker/restart)
        [[ -z "$TARGET" ]] || fail "Docker commands do not accept a target"
        ACTION_FILE="at/$PROFILE/docker.$ACTION.sh"
        ;;
    *) fail "unknown command: $AREA/$ACTION" ;;
esac

[[ -f "$ADMIN_DIR/$ACTION_FILE" ]] || fail "action file not found: $ACTION_FILE"
cd "$ROOT_DIR"

source "$ADMIN_DIR/bin/bootstrap.sh"
alina_bootstrap "$PROFILE"

if [[ "$AREA/$ACTION" == "sql/backup" || "$AREA/$ACTION" == "sql/restore" ]]; then
    contains_database "$TARGET" || fail "database '$TARGET' is not configured for profile '$PROFILE'"
fi

if [[ "${ALINA_DRY_DISPATCH:-0}" == "1" ]]; then
    printf 'profile=%s action=%s/%s target=%s\n' \
        "$PROFILE" "$AREA" "$ACTION" "${TARGET:--}"
    exit 0
fi

case "$AREA/$ACTION" in
    sql/backup)
        db="$TARGET"
        source "$ADMIN_DIR/$ACTION_FILE"
        ;;
    sql/restore)
        LOC_DB="$TARGET"
        source "$ADMIN_DIR/$ACTION_FILE"
        ;;
    *)
        source "$ADMIN_DIR/$ACTION_FILE"
        ;;
esac
