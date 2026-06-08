#! /bin/bash

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
export alina_rsync_local