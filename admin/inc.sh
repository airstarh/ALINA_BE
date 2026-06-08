#!/bin/bash

# Run:
# bash ./admin/deploy/tsk-code-to-remote.sh > ~/ln-log 2>&1

source ./admin/constants.sh

alina_rsync_ssh() {
    echo ""
    echo "STARTED alina_rsync_ssh"

    local SOURCE="$1"
    local TARGET="$2"

    ssh "${REMOTE_ADDR}" "sudo mkdir -p -m 755 ${TARGET}"
    local ssh_status=$?
    if [[ $ssh_status -ne 0 ]]; then
        echo "Error: SSH mkdir failed (exit: $ssh_status)" >&2
        return $ssh_status
    fi

    rsync \
        -rltv \
        -z \
        --skip-compress=jpg,jpeg,png,gif,mp4,mp3,zip,gz,pdf \
        --delete-after \
        --filter='- **/cfg/db.php' \
        --filter='- **/cfg/mailer.php' \
        --filter='- **/uploads/' \
        --filter='P **/uploads/' \
        -e "ssh" \
        --rsync-path="sudo rsync" \
        "${SOURCE}" \
        "${REMOTE_ADDR}:${TARGET}"

    local rsync_status=$?
    if [[ $rsync_status -ne 0 ]]; then
        echo "Error: sync failed (exit: $rsync_status)" >&2
    fi
    return $rsync_status
}

alina_rsync_local() {
    echo ""
    echo "STARTED alina_rsync_local"

    local SOURCE_BASE="$1"
    local SOURCE_DIFF="$2"
    local TARGET="$3"

    if [[ -z "$SOURCE_BASE" || -z "$SOURCE_DIFF" || -z "$TARGET" ]]; then
        echo "Error: Missing required arguments." >&2
        return 1
    fi

    if [[ ! -d "$SOURCE_BASE" ]]; then
        echo "Error: SOURCE_BASE not found: $SOURCE_BASE" >&2
        return 1
    fi

    if [[ ! -d "$SOURCE_DIFF" ]]; then
        echo "Error: SOURCE_DIFF not found: $SOURCE_DIFF" >&2
        return 1
    fi

    mkdir -p "$TARGET"
    local mkdir_status=$?
    if [[ $mkdir_status -ne 0 ]]; then
        echo "Error: Failed to create TARGET: $TARGET" >&2
        return $mkdir_status
    fi

    rsync \
        -av \
        --no-perms --no-owner --no-group \
        --delete \
        --filter='- **/uploads/' \
        --filter='P **/uploads/' \
        --filter='- **/apps/' \
        --filter='P **/apps/' \
        "${SOURCE_DIFF}" \
        "${SOURCE_BASE}" \
        "${TARGET}"

    local rsync_status=$?
    if [[ $rsync_status -eq 0 ]]; then
        echo "Sync completed successfully:"
        echo "  SOURCE_BASE: $SOURCE_BASE"
        echo "  SOURCE_DIFF: $SOURCE_DIFF"
        echo "  TARGET: $TARGET"
    else
        echo "Error: sync failed with exit code $rsync_status" >&2
    fi
    return $rsync_status
}