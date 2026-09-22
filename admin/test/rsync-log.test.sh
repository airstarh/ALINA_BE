#!/bin/bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
MOCK_BIN="$(mktemp -d)"
CAPTURE_FILE="$(mktemp)"
trap 'rm -r -- "$MOCK_BIN"; rm -f -- "$CAPTURE_FILE"' EXIT

fail() {
    echo "FAIL: $*" >&2
    exit 1
}

cat > "$MOCK_BIN/ssh" <<'EOF'
#!/bin/bash
exit 0
EOF

cat > "$MOCK_BIN/rsync" <<'EOF'
#!/bin/bash

for argument in "$@"; do
    case "$argument" in
        --log-file=*) log_file="${argument#--log-file=}" ;;
    esac
done

printf '%s\n' "$log_file" > "$RSYNC_TEST_CAPTURE"
printf 'rsync: failed test entry\n' >> "$log_file"
EOF

chmod +x "$MOCK_BIN/ssh" "$MOCK_BIN/rsync"

source "$ROOT_DIR/admin/bin/function/alina_rsync_to_remote.sh"
export ALINA_REMOTE_URL="test@example"
export RSYNC_TEST_CAPTURE="$CAPTURE_FILE"

PATH="$MOCK_BIN:$PATH" alina_rsync_to_remote /source /target >/dev/null

LOG_FILE="$(<"$CAPTURE_FILE")"
[[ "$(basename "$LOG_FILE")" == alina_rsync_errors.*.log ]] \
    || fail "alina rsync did not use its own unique log"
[[ ! -e "$LOG_FILE" ]] \
    || fail "alina rsync did not remove its log with the creating privilege"

echo "PASS: alina rsync log lifecycle"
