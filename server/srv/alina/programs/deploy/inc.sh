#!/bin/bash

source ./constants

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
rsyncSsh() {
        local SOURCE="$1"
        local TARGET="$2"

        ssh "${REMOTE_ADDR}" "sudo mkdir -p -m 755 ${TARGET}"

        rsync \
                -rltv \
                -z \
                --skip-compress=jpg/jpeg/png/gif/mp4/mp3/zip/gz/pdf \
                --delete-after \
                --filter='- **/cfg/db.php' \
                --filter='- **/cfg/mailer.php' \
                --filter='- **/uploads/' \
                --filter='P **/uploads/' \
                -e \
                "ssh" \
                --rsync-path="sudo rsync" \
                "${SOURCE}" \
                "${REMOTE_ADDR}:${TARGET}"
}

# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
# Purpose: Run rsync with protected directories (uploads/, apps/) and deletion
# Parameters:
#   $1 - SOURCE_BASE
#   $2 - SOURCE_DIFF
#   $3 - TARGET
sync_with_protection() {
    local SOURCE_BASE="$1"
    local SOURCE_DIFF="$2"
    local TARGET="$3"

    if [[ -z "$SOURCE_BASE" || -z "$SOURCE_DIFF" || -z "$TARGET" ]]; then
        echo "Error: Missing required arguments." >&2
        echo "Usage: sync_with_protection <SOURCE_BASE> <SOURCE_DIFF> <TARGET>" >&2
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
    if [[ ! -d "$TARGET" ]]; then
        echo "Error: Failed to create TARGET: $TARGET" >&2
        return 1
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

    if [[ $? -eq 0 ]]; then
        echo "Sync completed successfully:"
        echo "  SOURCE_BASE: $SOURCE_BASE"
        echo "  SOURCE_DIFF: $SOURCE_DIFF"
        echo "  TARGET: $TARGET"
    else
        echo "Error: rsync failed with exit code $?" >&2
        return $?
    fi
}
# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

