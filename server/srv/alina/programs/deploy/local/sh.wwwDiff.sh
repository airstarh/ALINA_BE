#!/bin/bash

# Function: sync_with_protection
# Purpose: Run rsync with protected directories (uploads/, apps/) and deletion
# Parameters:
#   $1 - SOURCE_BASE   (primary source directory)
#   $2 - SOURCE_DIFF  (secondary source directory, e.g., diff folder)
#   $3 - TARGET        (destination directory)

sync_with_protection() {
    local SOURCE_BASE="$1"
    local SOURCE_DIFF="$2"
    local TARGET="$3"

    # Validate required arguments
    if [[ -z "$SOURCE_BASE" || -z "$SOURCE_DIFF" || -z "$TARGET" ]]; then
        echo "Error: Missing required arguments." >&2
        echo "Usage: sync_with_protection <SOURCE_BASE> <SOURCE_DIFF> <TARGET>" >&2
        return 1
    fi

    # Check if source directories exist
    if [[ ! -d "$SOURCE_BASE" ]]; then
        echo "Error: SOURCE_BASE not found: $SOURCE_BASE" >&2
        return 1
    fi

    if [[ ! -d "$SOURCE_DIFF" ]]; then
        echo "Error: SOURCE_DIFF not found: $SOURCE_DIFF" >&2
        return 1
    fi

    # Create target directory if it doesn't exist
    mkdir -p "$TARGET"
    if [[ ! -d "$TARGET" ]]; then
        echo "Error: Failed to create TARGET: $TARGET" >&2
        return 1
    fi

    # Execute rsync with protection filters
    rsync \
        -av \
        --delete \
        --exclude='uploads/' \
        --exclude='apps/' \
        --existing \
        "$SOURCE_DIFF" \
        "$SOURCE_BASE" \
        "$TARGET"

    # Check rsync exit status
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

BASE="zero.home"
SOURCE_BASE="/home/qqq/a/b/server/srv/alina_consumers/${BASE}/.WwwDiff/"

# zero.home
PROJECT="zero.home"
SOURCE_DIFF="/home/qqq/a/b/server/srv/alina_consumers/${PROJECT}/.WwwDiff/"
TARGET="/home/qqq/a/b/server/var/www/${PROJECT}/"
sync_with_protection "$SOURCE_BASE" "$SOURCE_DIFF" "$TARGET"

# STAGE
PROJECT="stage"
SOURCE_DIFF="/home/qqq/a/b/server/srv/alina_consumers/${PROJECT}/.WwwDiff/"
TARGET="/home/qqq/a/b/server/var/www/${PROJECT}/"
sync_with_protection "$SOURCE_BASE" "$SOURCE_DIFF" "$TARGET"
