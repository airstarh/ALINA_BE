#!/bin/bash

alina_rsync_to_remote() {
    local SOURCE="$1"
    local TARGET="$2"

    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "RSYNC..."
    echo "📌 FROM---$SOURCE"
    echo "📌 TO-----${ALINA_REMOTE_URL}:${TARGET}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    ssh "${ALINA_REMOTE_URL%%:*}" "mkdir -p \"${TARGET}\""

    local changes
    changes=$(rsync \
        -rltLvz \
        --itemize-changes \
        --skip-compress=jpg,jpeg,png,gif,mp4,mp3,zip,gz,pdf \
        --delete-after \
        --no-perms \
        --no-owner \
        --no-group \
        --omit-dir-times \
        --checksum \
        --filter='- **/cfg/db.php' \
        --filter='- **/cfg/mailer.php' \
        --filter='- **/*code-workspace' \
        --filter='- **/database/' \
        --filter='P **/database/' \
        --filter='- **/log/' \
        --filter='P **/log/' \
        --filter='- **/uploads/' \
        --filter='P **/uploads/' \
        --filter='- **/.git/' \
        --filter='P **/.git/' \
        --filter='- **/.idea/' \
        --filter='P **/.idea/' \
        --filter='- **/.vscode/' \
        --filter='P **/.vscode/' \
        --filter='- **/nbproject/' \
        --filter='P **/nbproject/' \
        --filter='- **/letsencrypt/' \
        --filter='P **/letsencrypt/' \
        --filter='- **/node_modules/' \
        --filter='P **/node_modules/' \
        --filter='- **/_GITOUT/' \
        --filter='P **/_GITOUT/' \
        -e "ssh" \
        --log-file=/tmp/rsync_errors.log \
        --rsync-path="sudo rsync" \
        --force \
        --whole-file \
        "${SOURCE}/" \
        "${ALINA_REMOTE_URL}:${TARGET}/")

    local rsync_status=$?
    if [[ $rsync_status -ne 0 ]] && [[ $rsync_status -ne 24 ]]; then
        echo "❌ Error: rsync failed (exit: $rsync_status)" >&2
        return $rsync_status
    fi

    echo "$changes" | while IFS= read -r line; do
        [[ -z "$line" ]] && continue
        line=$(echo "$line" | sed 's/\x1b\[[0-9;]*m//g')

        local prefix="${line:0:1}"
        local code="${line:1:10}"
        local filepath="${line:12}"

        [[ "$line" =~ ^\[ || "$line" =~ ^sending\ incremental || "$line" =~ ^recv || "$line" =~ ^total\ size ]] && continue

        if [[ "$line" =~ ^\*deleting ]]; then
            echo "🗑️  [DELETE] $filepath"
            continue
        fi

        if [[ "$code" =~ ^d ]]; then
            if [[ "$code" == "d+++++++++" ]]; then
                echo "📁 [CREATE] $filepath"
            elif [[ "$code" =~ s|t|p|o|g ]]; then
                echo "🔧 [UPDATE] $filepath"
            fi
            continue
        fi

        case "$code" in
            "f+++++++++") echo "📤 [UPLOAD] $filepath" ;;
            "f.st......"|*"c") echo "🔄 [UPDATE] $filepath" ;;
            "<f.st......"|*"<f"c*) echo "🔄 [UPDATE] $filepath" ;;
            *)
                if [[ "$prefix" == "<" ]]; then
                    echo "📤 [UPLOAD] $filepath"
                else
                    echo "📥 [DOWNLOAD] $filepath"
                fi
                ;;
        esac
    done

    local upload_count=$(echo "$changes" | grep -c "^<f")
    local delete_count=$(echo "$changes" | grep -c "^\*deleting")
    local create_dir_count=$(echo "$changes" | grep -c "^cd")
    local update_count=$(echo "$changes" | grep -c "^<f.st\|^<f..c")

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "${ALINA_REMOTE_URL}:${TARGET}"
    echo "TOTALS"
    [[ $upload_count -gt 0 ]]     && echo "Upload: $upload_count file(s)"
    [[ $update_count -gt 0 ]]     && echo "Update: $update_count file(s)"
    [[ $create_dir_count -gt 0 ]] && echo "Create: $create_dir_count directory(ies)"
    [[ $delete_count -gt 0 ]]     && echo "Delete: $delete_count file(s)"
    [[ $upload_count -eq 0 && $update_count -eq 0 && $delete_count -eq 0 ]] && echo "No changes to sync"

    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Files that failed/were skipped (from log) ==="
    grep -E "failed|skipped|error" /tmp/rsync_errors.log || echo "No errors found"
    rm -f /tmp/rsync_errors.log

    return $rsync_status
}

export alina_rsync_to_remote
