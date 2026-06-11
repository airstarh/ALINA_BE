#!/bin/bash

alina_rsync_local() {
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

    local rsync_output
    rsync_output=$(rsync \
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
        "$TARGET/" 2>&1)

    local rsync_status=$?

    local added_count=0
    local updated_count=0
    local deleted_count=0

    while IFS= read -r line; do
        line=$(echo "$line" | tr -s ' ')
        if [[ "$line" =~ ^deleting\ .+ ]]; then
            deleted_count=$((deleted_count + 1))
        elif [[ "$line" =~ ^\>f ]]; then
            added_count=$((added_count + 1))
        elif [[ "$line" =~ ^\.[f] ]]; then
            updated_count=$((updated_count + 1))
        fi
    done <<< "$rsync_output"

    echo "$rsync_output" | sed '/^$/d' || true
    echo

    printf "Summary:\n"
    printf "   Added:     %3d file(s)\n" "$added_count"
    printf "   Updated:   %3d file(s)\n" "$updated_count"
    printf "   Deleted:   %3d file(s)\n" "$deleted_count"
    echo

    local total_changes=$((added_count + updated_count + deleted_count))
    if [[ $total_changes -eq 0 ]]; then
        echo "No changes detected."
    else
        echo "Total changes: $total_changes"
    fi

    return 0
}

export -f alina_rsync_local
