#!/bin/bash

alina_rsync_from_remote() {
    local TARGET="$1"
    local SOURCE="$2"

    echo "🔍 Проверка изменений (dry-run)..."
    echo "📌 Источник: ${ALINA_REMOTE_URL}:${SOURCE}"
    echo "📌 Назначение: $TARGET"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    local rsync_cmd=(
        rsync
        -rltLv
        -z
        --itemize-changes
        --skip-compress=jpg,jpeg,png,gif,mp4,mp3,zip,gz,pdf
        --delete-after
        --no-perms
        --no-owner
        --no-group
        --omit-dir-times
        --no-times
        --checksum
        --filter='- **/cfg/db.php'
        --filter='- **/cfg/mailer.php'
        --filter='- **/*code-workspace'
        --filter='- **/database/'
        --filter='P **/database/'
        --filter='- **/log/'
        --filter='P **/log/'
        --filter='- **/uploads/'
        --filter='P **/uploads/'
        --filter='- **/.git/'
        --filter='P **/.git/'
        --filter='- **/_GITOUT/'
        --filter='P **/_GITOUT/'
        --filter='- **/.idea/'
        --filter='P **/.idea/'
        --filter='- **/.vscode/'
        --filter='P **/.vscode/'
        --filter='- **/nbproject/'
        --filter='P **/nbproject/'
        --filter='- **/letsencrypt/'
        --filter='P **/letsencrypt/'
        --filter='- **/node_modules/'
        --filter='P **/node_modules/'
        -e "ssh -i ${ALINA_REMOTE_SSH} -o StrictHostKeyChecking=no"
        --rsync-path="rsync"
        --force
        --whole-file
        "${ALINA_REMOTE_URL}:${SOURCE}/"
        "${TARGET}/"
    )

    local changes
    changes=$(sudo "${rsync_cmd[@]}" --dry-run)

    local rsync_status=$?
    if [[ $rsync_status -ne 0 ]] && [[ $rsync_status -ne 24 ]]; then
        echo "❌ Ошибка: rsync (dry-run) завершился с кодом $rsync_status" >&2
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
            "f+++++++++") echo "📥 [DOWNLOAD] $filepath" ;;
            "f.st......"|*"c") echo "🔄 [UPDATE] $filepath" ;;
            ">f.st......"|*">f"c*) echo "🔄 [UPDATE] $filepath" ;;
            *)
                if [[ "$prefix" == ">" ]]; then
                    echo "📥 [DOWNLOAD] $filepath"
                else
                    echo "📤 [UPLOAD] $filepath"
                fi
                ;;
        esac
    done

    local download_count=$(echo "$changes" | grep -c "^>f")
    local delete_count=$(echo "$changes" | grep -c "^\*deleting")
    local create_dir_count=$(echo "$changes" | grep -c "^cd")
    local update_count=$(echo "$changes" | grep -c "^>f.st\|^>f..c")

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "${ALINA_REMOTE_URL}:${SOURCE} → $TARGET"
    echo "📊 Статистика (предварительная):"
    [[ $download_count -gt 0 ]]   && echo "📥 Будет загружено: $download_count файл(ов)"
    [[ $update_count -gt 0 ]]     && echo "🔄 Будет обновлено: $update_count файл(ов)"
    [[ $create_dir_count -gt 0 ]] && echo "📁 Будет создано: $create_dir_count папок(и)"
    [[ $delete_count -gt 0 ]]     && echo "🗑️  Будет удалено: $delete_count файл(ов)"
    [[ $download_count -eq 0 && $update_count -eq 0 && $delete_count -eq 0 ]] && echo "✅ Изменений нет"

    echo
    echo "🚀 Запуск синхронизации..."
    sudo "${rsync_cmd[@]}"

    rsync_status=$?
    if [[ $rsync_status -eq 0 ]]; then
        echo "✅ Синхронизация завершена"
    else
        echo "❌ Ошибка при синхронизации: код $rsync_status" >&2
    fi

    return $rsync_status
}

export alina_rsync_from_remote
