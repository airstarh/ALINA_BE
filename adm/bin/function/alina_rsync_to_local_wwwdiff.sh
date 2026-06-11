#!/bin/bash

alina_rsync_to_local_wwwdiff() {
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
    if [[ $? -ne 0 ]]; then
        echo "Error: Failed to create TARGET: $TARGET" >&2
        return 1
    fi

    rsync \
        -rltDv \
        --no-perms --no-owner --no-group \
        --itemize-changes \
        --delete \
        --force \
        --filter='- **/uploads/' \
        --filter='P **/uploads/' \
        --filter='- **/apps/' \
        --filter='P **/apps/' \
        "$SOURCE_DIFF/" \
        "$SOURCE_BASE/" \
        "$TARGET/"
}

export -f alina_rsync_to_local_wwwdiff
